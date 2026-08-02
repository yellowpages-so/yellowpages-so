<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class VerificationService
{
    public function createClaim(
        Business $business,
        User $user,
        array $data
    ): BusinessClaim {
        $duplicate = BusinessClaim::query()
            ->where('business_id', $business->id)
            ->where('claimant_user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($duplicate) {
            throw new RuntimeException(
                'You already have an active claim for this business.'
            );
        }

        return DB::transaction(function () use ($business, $user, $data): BusinessClaim {
            $claim = BusinessClaim::query()->create([
                'id' => (string) Str::uuid(),
                'business_id' => $business->id,
                'claimant_user_id' => $user->id,
                'claim_type' => $data['claim_type'],
                'claim_reason' => $data['claim_reason'],
                'contact_email' => $data['contact_email'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'evidence_summary' => $data['evidence_summary'] ?? null,
                'status' => 'pending',
                'submitted_at' => now(),
                'created_at' => now(),
            ]);

            $this->recordHistory(
                $business->id,
                null,
                $user->id,
                'claim_submitted',
                null,
                'pending',
                ['claim_id' => $claim->id]
            );

            return $claim;
        });
    }

    public function createVerificationRequest(
        Business $business,
        User $user,
        string $levelCode
    ): VerificationRequest {
        $isMember = DB::table('directory.business_members')
            ->where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (! $isMember) {
            throw new RuntimeException('You do not manage this business.');
        }

        $levelId = DB::table('verification.verification_levels')
            ->where('code', $levelCode)
            ->value('id');

        if (! $levelId) {
            throw new RuntimeException('Verification level not found.');
        }

        $activeRequest = VerificationRequest::query()
            ->where('business_id', $business->id)
            ->whereIn('status', ['submitted', 'under_review', 'information_requested'])
            ->first();

        if ($activeRequest) {
            return $activeRequest;
        }

        return DB::transaction(function () use ($business, $user, $levelId): VerificationRequest {
            $request = VerificationRequest::query()->create([
                'id' => (string) Str::uuid(),
                'business_id' => $business->id,
                'reference_no' => 'VER-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
                'requested_level_id' => $levelId,
                'status' => 'submitted',
                'current_step' => 'documents',
                'risk_score' => 0,
                'submitted_by' => $user->id,
                'submitted_at' => now(),
                'expires_at' => now()->addDays(30),
            ]);

            foreach ([
                'contact',
                'identity',
                'business_license',
                'tax',
                'location',
            ] as $checkType) {
                DB::table('verification.verification_checks')->insert([
                    'id' => (string) Str::uuid(),
                    'request_id' => $request->id,
                    'check_type' => $checkType,
                    'status' => 'pending',
                ]);
            }

            $this->recordHistory(
                $business->id,
                $request->id,
                $user->id,
                'verification_submitted',
                null,
                'submitted',
                []
            );

            return $request;
        });
    }

    public function storeDocument(
        VerificationRequest $request,
        User $user,
        array $data,
        UploadedFile $file
    ): array {
        $belongsToUser = DB::table('directory.business_members')
            ->where('business_id', $request->business_id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (! $belongsToUser) {
            throw new RuntimeException('You do not manage this business.');
        }

        $checksum = hash_file('sha256', $file->getRealPath());
        $path = $file->store(
            "verification/{$request->business_id}/{$request->id}",
            'local'
        );

        $id = (string) Str::uuid();

        DB::table('verification.verification_documents')->insert([
            'id' => $id,
            'request_id' => $request->id,
            'document_type' => $data['document_type'],
            'storage_key' => $path,
            'document_number' => $data['document_number'] ?? null,
            'issued_at' => $data['issued_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'status' => 'submitted',
            'uploaded_by' => $user->id,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'checksum_sha256' => $checksum,
            'virus_scan_passed' => false,
            'created_at' => now(),
        ]);

        $this->recordHistory(
            $request->business_id,
            $request->id,
            $user->id,
            'document_uploaded',
            null,
            'submitted',
            ['document_id' => $id, 'document_type' => $data['document_type']]
        );

        return [
            'id' => $id,
            'document_type' => $data['document_type'],
            'status' => 'submitted',
            'original_name' => $file->getClientOriginalName(),
            'checksum_sha256' => $checksum,
        ];
    }

    public function decide(
        VerificationRequest $request,
        User $actor,
        array $data
    ): VerificationRequest {
        return DB::transaction(function () use ($request, $actor, $data): VerificationRequest {
            $oldStatus = $request->status;

            if ($data['decision'] === 'approved') {
                $levelId = DB::table('verification.verification_levels')
                    ->where('code', $data['approved_level_code'])
                    ->value('id');

                if (! $levelId) {
                    throw new RuntimeException('Approved level not found.');
                }

                DB::table('verification.verification_decisions')->updateOrInsert(
                    ['request_id' => $request->id],
                    [
                        'id' => DB::table('verification.verification_decisions')
                            ->where('request_id', $request->id)
                            ->value('id') ?? (string) Str::uuid(),
                        'decision' => 'approved',
                        'approved_level_id' => $levelId,
                        'reason' => $data['reason'] ?? null,
                        'decided_by' => $actor->id,
                        'created_at' => now(),
                    ]
                );

                DB::table('directory.businesses')
                    ->where('id', $request->business_id)
                    ->update(['verification_level_id' => $levelId]);

                $newStatus = 'approved';
            } elseif ($data['decision'] === 'information_requested') {
                $newStatus = 'information_requested';
            } else {
                $newStatus = 'rejected';
            }

            $request->update([
                'status' => $newStatus,
                'current_step' => $newStatus,
                'decided_at' => in_array($newStatus, ['approved', 'rejected'], true)
                    ? now()
                    : null,
                'rejection_reason' => $newStatus === 'rejected'
                    ? ($data['reason'] ?? null)
                    : null,
            ]);

            $this->recordHistory(
                $request->business_id,
                $request->id,
                $actor->id,
                'verification_decision',
                $oldStatus,
                $newStatus,
                ['reason' => $data['reason'] ?? null]
            );

            DB::table('system.audit_logs')->insert([
                'actor_user_id' => $actor->id,
                'action' => 'verification.'.$newStatus,
                'entity_type' => 'verification_request',
                'entity_id' => $request->id,
                'after_data' => json_encode($data),
                'created_at' => now(),
            ]);

            return $request->fresh();
        });
    }

    private function recordHistory(
        string $businessId,
        ?string $requestId,
        ?string $actorUserId,
        string $eventType,
        ?string $oldStatus,
        ?string $newStatus,
        array $metadata
    ): void {
        DB::table('verification.verification_history')->insert([
            'id' => (string) Str::uuid(),
            'business_id' => $businessId,
            'request_id' => $requestId,
            'actor_user_id' => $actorUserId,
            'event_type' => $eventType,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'metadata' => json_encode($metadata),
            'created_at' => now(),
        ]);
    }
}
