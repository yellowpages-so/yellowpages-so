<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMediaRequest;
use App\Http\Requests\UploadMediaRequest;
use App\Services\MediaManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MediaController extends Controller
{
    public function __construct(
        private readonly MediaManagementService $service
    ) {}

    public function upload(
        UploadMediaRequest $request
    ): JsonResponse {
        try {
            $asset = $this->service->upload(
                $request->user(),
                $request->file('file'),
                $request->validated()
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Media uploaded successfully.',
            'data' => $asset,
        ], 201);
    }

    public function show(
        Request $request,
        string $assetId
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $this->service->asset($assetId),
        ]);
    }

    public function update(
        UpdateMediaRequest $request,
        string $assetId
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => 'Media updated successfully.',
            'data' => $this->service->update(
                $request->user(),
                $assetId,
                $request->validated()
            ),
        ]);
    }

    public function destroy(
        Request $request,
        string $assetId
    ): JsonResponse {
        $this->service->delete(
            $request->user(),
            $assetId
        );

        return response()->json([
            'success' => true,
            'message' => 'Media deleted successfully.',
        ]);
    }

    public function collection(
        Request $request
    ): JsonResponse {
        $data = $request->validate([
            'owner_type' => ['required', 'string'],
            'owner_id' => ['required', 'uuid'],
            'collection' => ['required', 'string'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->service->collection(
                $data['owner_type'],
                $data['owner_id'],
                $data['collection']
            ),
        ]);
    }

    public function download(
        Request $request,
        string $assetId
    ) {
        $asset = DB::table('media.assets')
            ->where('id', $assetId)
            ->whereNull('deleted_at')
            ->first();

        abort_if(! $asset, 404);

        if ($asset->visibility === 'private') {
            abort_unless(
                $request->hasValidSignature(),
                403,
                'A valid signed URL is required.'
            );
        }

        DB::table('media.access_logs')->insert([
            'id' => (string) Str::uuid(),
            'asset_id' => $assetId,
            'user_id' => $request->user()?->id,
            'action' => 'download',
            'ip_hash' => hash(
                'sha256',
                (string) $request->ip()
            ),
            'created_at' => now(),
        ]);

        return Storage::disk($asset->disk)->download(
            $asset->path,
            $asset->original_name
        );
    }
}
