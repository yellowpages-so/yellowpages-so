<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminPortalService
{
    public function dashboard(): array
    {
        return [
            'users' => [
                'total' => DB::table('iam.users')->whereNull('deleted_at')->count(),
                'active' => DB::table('iam.users')->where('status', 'active')->whereNull('deleted_at')->count(),
                'suspended' => DB::table('iam.users')->where('status', 'suspended')->whereNull('deleted_at')->count(),
            ],
            'businesses' => [
                'total' => DB::table('directory.businesses')->whereNull('deleted_at')->count(),
                'draft' => DB::table('directory.businesses')->where('status', 'draft')->whereNull('deleted_at')->count(),
                'published' => DB::table('directory.businesses')->where('status', 'published')->whereNull('deleted_at')->count(),
            ],
            'verification' => [
                'submitted' => DB::table('verification.verification_requests')->where('status', 'submitted')->count(),
                'under_review' => DB::table('verification.verification_requests')->where('status', 'under_review')->count(),
                'information_requested' => DB::table('verification.verification_requests')->where('status', 'information_requested')->count(),
            ],
            'reviews' => [
                'total' => DB::table('reviews.reviews')->count(),
                'reported' => DB::table('reviews.review_reports')->count(),
            ],
            'leads' => [
                'total' => DB::table('leads.leads')->count(),
                'open' => DB::table('leads.leads')->whereIn('status', ['new', 'open', 'assigned'])->count(),
            ],
        ];
    }

    public function updateUserStatus(User $actor, string $userId, array $data): void
    {
        DB::transaction(function () use ($actor, $userId, $data): void {
            $before = DB::table('iam.users')->where('id', $userId)->value('status');

            DB::table('iam.users')->where('id', $userId)->update([
                'status' => $data['status'],
                'updated_at' => now(),
            ]);

            $this->record(
                $actor,
                'user.status_changed',
                'user',
                $userId,
                ['before' => $before, 'after' => $data['status'], 'reason' => $data['reason']]
            );
        });
    }

    public function updateBusinessStatus(User $actor, string $businessId, array $data): void
    {
        DB::transaction(function () use ($actor, $businessId, $data): void {
            $before = DB::table('directory.businesses')->where('id', $businessId)->value('status');

            DB::table('directory.businesses')->where('id', $businessId)->update([
                'status' => $data['status'],
                'published_at' => $data['status'] === 'published' ? now() : null,
                'updated_at' => now(),
            ]);

            $this->record(
                $actor,
                'business.status_changed',
                'business',
                $businessId,
                ['before' => $before, 'after' => $data['status'], 'reason' => $data['reason']]
            );
        });
    }

    public function addNote(User $actor, string $entityType, string $entityId, string $note): string
    {
        $id = (string) Str::uuid();

        DB::table('system.admin_notes')->insert([
            'id' => $id,
            'actor_user_id' => $actor->id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'note' => $note,
            'created_at' => now(),
        ]);

        $this->record(
            $actor,
            'admin.note_added',
            $entityType,
            $entityId,
            ['note_id' => $id]
        );

        return $id;
    }

    private function record(User $actor, string $action, string $entityType, string $entityId, array $payload): void
    {
        DB::table('system.admin_actions')->insert([
            'id' => (string) Str::uuid(),
            'actor_user_id' => $actor->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'payload' => json_encode($payload),
            'created_at' => now(),
        ]);

        DB::table('system.audit_logs')->insert([
            'actor_user_id' => $actor->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'after_data' => json_encode($payload),
            'created_at' => now(),
        ]);
    }
}
