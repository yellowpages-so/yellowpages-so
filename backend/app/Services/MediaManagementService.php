<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MediaManagementService
{
    public function upload(
        User $user,
        UploadedFile $file,
        array $data
    ): array {
        $this->authorizeOwner(
            $user,
            $data['owner_type'],
            $data['owner_id'],
            $data['business_id'] ?? null
        );

        $this->validateMime(
            $file,
            $data['collection']
        );

        $id = (string) Str::uuid();
        $extension = strtolower(
            $file->getClientOriginalExtension()
                ?: $file->extension()
                ?: 'bin'
        );

        $directory = sprintf(
            'media/%s/%s/%s',
            $data['owner_type'],
            $data['owner_id'],
            $data['collection']
        );

        $filename = $id.'.'.$extension;
        $disk = config('media.disk');
        $path = $file->storeAs(
            $directory,
            $filename,
            $disk
        );

        if (! $path) {
            throw new RuntimeException('Unable to store media file.');
        }

        $absolutePath = Storage::disk($disk)->path($path);
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $dimensions = $this->dimensions($absolutePath, $mime);
        $checksum = hash_file('sha256', $absolutePath);

        DB::transaction(function () use (
            $id,
            $user,
            $data,
            $file,
            $disk,
            $path,
            $mime,
            $extension,
            $dimensions,
            $checksum
        ): void {
            DB::table('media.assets')->insert([
                'id' => $id,
                'uploaded_by' => $user->id,
                'business_id' => $data['business_id'] ?? null,
                'owner_type' => $data['owner_type'],
                'owner_id' => $data['owner_id'],
                'collection' => $data['collection'],
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $mime,
                'extension' => $extension,
                'size_bytes' => $file->getSize(),
                'checksum_sha256' => $checksum,
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
                'status' => 'ready',
                'visibility' => $data['visibility'],
                'alt_text' => $data['alt_text'] ?? null,
                'caption' => $data['caption'] ?? null,
                'metadata' => json_encode([
                    'client_mime' => $file->getClientMimeType(),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->syncTags(
                $id,
                $data['tags'] ?? []
            );

            DB::table('media.processing_jobs')->insert([
                'id' => (string) Str::uuid(),
                'asset_id' => $id,
                'job_type' => str_starts_with($mime, 'image/')
                    ? 'image_variants'
                    : 'metadata_scan',
                'status' => 'pending',
                'attempts' => 0,
                'input' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return $this->asset($id);
    }

    public function update(
        User $user,
        string $assetId,
        array $data
    ): array {
        $asset = DB::table('media.assets')
            ->where('id', $assetId)
            ->whereNull('deleted_at')
            ->first();

        if (! $asset) {
            throw new RuntimeException('Media asset not found.');
        }

        $this->authorizeOwner(
            $user,
            $asset->owner_type,
            $asset->owner_id,
            $asset->business_id
        );

        DB::transaction(function () use (
            $assetId,
            $data
        ): void {
            DB::table('media.assets')
                ->where('id', $assetId)
                ->update([
                    'alt_text' => $data['alt_text'] ?? null,
                    'caption' => $data['caption'] ?? null,
                    'sort_order' => $data['sort_order'] ?? 0,
                    'visibility' => $data['visibility'] ?? 'public',
                    'updated_at' => now(),
                ]);

            if (array_key_exists('tags', $data)) {
                $this->syncTags(
                    $assetId,
                    $data['tags'] ?? []
                );
            }
        });

        return $this->asset($assetId);
    }

    public function delete(
        User $user,
        string $assetId
    ): void {
        $asset = DB::table('media.assets')
            ->where('id', $assetId)
            ->whereNull('deleted_at')
            ->first();

        if (! $asset) {
            throw new RuntimeException('Media asset not found.');
        }

        $this->authorizeOwner(
            $user,
            $asset->owner_type,
            $asset->owner_id,
            $asset->business_id
        );

        DB::table('media.assets')
            ->where('id', $assetId)
            ->update([
                'status' => 'deleted',
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function asset(string $assetId): array
    {
        $asset = DB::table('media.assets')
            ->where('id', $assetId)
            ->whereNull('deleted_at')
            ->first();

        if (! $asset) {
            throw new RuntimeException('Media asset not found.');
        }

        $variants = DB::table('media.asset_variants')
            ->where('asset_id', $assetId)
            ->get()
            ->map(fn ($variant): array => [
                ...(array) $variant,
                'url' => Storage::disk($variant->disk)
                    ->url($variant->path),
            ])
            ->all();

        return [
            ...(array) $asset,
            'url' => $asset->visibility === 'public'
                ? Storage::disk($asset->disk)->url($asset->path)
                : null,
            'variants' => $variants,
            'tags' => DB::table('media.asset_tags')
                ->where('asset_id', $assetId)
                ->pluck('tag')
                ->all(),
        ];
    }

    public function collection(
        string $ownerType,
        string $ownerId,
        string $collection
    ): array {
        return DB::table('media.assets')
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('collection', $collection)
            ->where('status', 'ready')
            ->where('visibility', 'public')
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($asset): array {
                return [
                    ...(array) $asset,
                    'url' => Storage::disk($asset->disk)
                        ->url($asset->path),
                ];
            })
            ->all();
    }

    public function processPending(int $limit = 50): int
    {
        $jobs = DB::table('media.processing_jobs')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        $processed = 0;

        foreach ($jobs as $job) {
            DB::table('media.processing_jobs')
                ->where('id', $job->id)
                ->update([
                    'status' => 'processing',
                    'attempts' => $job->attempts + 1,
                    'started_at' => now(),
                    'updated_at' => now(),
                ]);

            try {
                $asset = DB::table('media.assets')
                    ->where('id', $job->asset_id)
                    ->first();

                if ($asset && str_starts_with(
                    $asset->mime_type,
                    'image/'
                )) {
                    $this->createOriginalVariant($asset);
                }

                DB::table('media.processing_jobs')
                    ->where('id', $job->id)
                    ->update([
                        'status' => 'completed',
                        'output' => json_encode([
                            'processed' => true,
                        ]),
                        'completed_at' => now(),
                        'updated_at' => now(),
                    ]);

                $processed++;
            } catch (\Throwable $exception) {
                DB::table('media.processing_jobs')
                    ->where('id', $job->id)
                    ->update([
                        'status' => 'failed',
                        'error_message' => $exception->getMessage(),
                        'completed_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        }

        return $processed;
    }

    private function createOriginalVariant(
        object $asset
    ): void {
        DB::table('media.asset_variants')->updateOrInsert(
            [
                'asset_id' => $asset->id,
                'variant' => 'original',
            ],
            [
                'id' => DB::table('media.asset_variants')
                    ->where('asset_id', $asset->id)
                    ->where('variant', 'original')
                    ->value('id') ?? (string) Str::uuid(),
                'disk' => $asset->disk,
                'path' => $asset->path,
                'mime_type' => $asset->mime_type,
                'size_bytes' => $asset->size_bytes,
                'width' => $asset->width,
                'height' => $asset->height,
                'created_at' => now(),
            ]
        );
    }

    private function syncTags(
        string $assetId,
        array $tags
    ): void {
        DB::table('media.asset_tags')
            ->where('asset_id', $assetId)
            ->delete();

        foreach (collect($tags)
            ->map(fn ($tag) => Str::lower(trim((string) $tag)))
            ->filter()
            ->unique()
            ->take(20) as $tag) {
            DB::table('media.asset_tags')->insert([
                'id' => (string) Str::uuid(),
                'asset_id' => $assetId,
                'tag' => $tag,
                'created_at' => now(),
            ]);
        }
    }

    private function validateMime(
        UploadedFile $file,
        string $collection
    ): void {
        $mime = $file->getMimeType();

        $allowed = match ($collection) {
            'document', 'verification' => config('media.allowed_document_mimes'),
            'video' => config('media.allowed_video_mimes'),
            default => config('media.allowed_image_mimes'),
        };

        if (! in_array($mime, $allowed, true)) {
            throw new RuntimeException(
                'The uploaded file type is not allowed.'
            );
        }

        $maxMb = match ($collection) {
            'document', 'verification' => config('media.max_document_mb'),
            'video' => config('media.max_video_mb'),
            default => config('media.max_image_mb'),
        };

        if ($file->getSize() > ($maxMb * 1024 * 1024)) {
            throw new RuntimeException(
                "The uploaded file exceeds {$maxMb} MB."
            );
        }
    }

    private function dimensions(
        string $path,
        string $mime
    ): array {
        if (! str_starts_with($mime, 'image/')) {
            return [
                'width' => null,
                'height' => null,
            ];
        }

        $size = @getimagesize($path);

        return [
            'width' => $size[0] ?? null,
            'height' => $size[1] ?? null,
        ];
    }

    private function authorizeOwner(
        User $user,
        string $ownerType,
        string $ownerId,
        ?string $businessId
    ): void {
        if ($ownerType === 'user' && $ownerId === $user->id) {
            return;
        }

        $resolvedBusinessId = $businessId;

        if ($ownerType === 'business') {
            $resolvedBusinessId = $ownerId;
        }

        if (! $resolvedBusinessId) {
            throw new RuntimeException(
                'A business association is required.'
            );
        }

        $allowed = DB::table('directory.business_members')
            ->where('business_id', $resolvedBusinessId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (! $allowed) {
            throw new RuntimeException(
                'You do not manage this business.'
            );
        }
    }
}
