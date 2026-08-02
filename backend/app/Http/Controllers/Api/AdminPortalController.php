<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNoteRequest;
use App\Http\Requests\AdminUpdateBusinessStatusRequest;
use App\Http\Requests\AdminUpdateUserStatusRequest;
use App\Services\AdminPortalService;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPortalController extends Controller
{
    public function __construct(
        private readonly AdminPortalService $service
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => $this->service->dashboard(),
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        $users = DB::table('iam.users as users')
            ->leftJoin('iam.user_profiles as profiles', 'profiles.user_id', '=', 'users.id')
            ->leftJoin('iam.user_emails as emails', function ($join): void {
                $join->on('emails.user_id', '=', 'users.id')
                    ->where('emails.is_primary', true);
            })
            ->whereNull('users.deleted_at')
            ->select([
                'users.id',
                'users.public_id',
                'users.status',
                'users.email_verified_at',
                'users.phone_verified_at',
                'users.last_login_at',
                'users.created_at',
                'profiles.display_name',
                'profiles.first_name',
                'profiles.last_name',
                'emails.email',
            ])
            ->orderByDesc('users.created_at')
            ->paginate(25);

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function updateUserStatus(
        AdminUpdateUserStatusRequest $request,
        string $userId
    ): JsonResponse {
        AdminAccess::authorize($request->user());

        abort_unless(
            DB::table('iam.users')->where('id', $userId)->exists(),
            404,
            'User not found.'
        );

        $this->service->updateUserStatus(
            $request->user(),
            $userId,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully.',
        ]);
    }

    public function businesses(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        $businesses = DB::table('directory.businesses as businesses')
            ->leftJoin('iam.user_profiles as profiles', 'profiles.user_id', '=', 'businesses.created_by')
            ->whereNull('businesses.deleted_at')
            ->select([
                'businesses.id',
                'businesses.public_id',
                'businesses.legal_name',
                'businesses.trading_name',
                'businesses.slug',
                'businesses.status',
                'businesses.profile_completeness',
                'businesses.verification_level_id',
                'businesses.created_at',
                'profiles.display_name as created_by_name',
            ])
            ->orderByDesc('businesses.created_at')
            ->paginate(25);

        return response()->json(['success' => true, 'data' => $businesses]);
    }

    public function updateBusinessStatus(
        AdminUpdateBusinessStatusRequest $request,
        string $businessId
    ): JsonResponse {
        AdminAccess::authorize($request->user());

        abort_unless(
            DB::table('directory.businesses')->where('id', $businessId)->exists(),
            404,
            'Business not found.'
        );

        $this->service->updateBusinessStatus(
            $request->user(),
            $businessId,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Business status updated successfully.',
        ]);
    }

    public function verificationQueue(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        $queue = DB::table('verification.verification_requests as requests')
            ->join('directory.businesses as businesses', 'businesses.id', '=', 'requests.business_id')
            ->leftJoin('iam.user_profiles as profiles', 'profiles.user_id', '=', 'requests.submitted_by')
            ->select([
                'requests.id',
                'requests.reference_no',
                'requests.status',
                'requests.current_step',
                'requests.risk_score',
                'requests.submitted_at',
                'businesses.public_id as business_public_id',
                'businesses.trading_name',
                'profiles.display_name as submitted_by_name',
            ])
            ->orderBy('requests.submitted_at')
            ->paginate(25);

        return response()->json(['success' => true, 'data' => $queue]);
    }

    public function auditLog(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        $logs = DB::table('system.admin_actions as actions')
            ->leftJoin('iam.user_profiles as profiles', 'profiles.user_id', '=', 'actions.actor_user_id')
            ->select([
                'actions.id',
                'actions.action',
                'actions.entity_type',
                'actions.entity_id',
                'actions.payload',
                'actions.created_at',
                'profiles.display_name as actor_name',
            ])
            ->orderByDesc('actions.created_at')
            ->paginate(50);

        return response()->json(['success' => true, 'data' => $logs]);
    }

    public function addNote(
        AdminNoteRequest $request,
        string $entityType,
        string $entityId
    ): JsonResponse {
        AdminAccess::authorize($request->user());

        abort_unless(
            in_array(
                $entityType,
                ['user', 'business', 'verification_request', 'review', 'lead'],
                true
            ),
            422,
            'Unsupported entity type.'
        );

        $id = $this->service->addNote(
            $request->user(),
            $entityType,
            $entityId,
            $request->validated()['note']
        );

        return response()->json([
            'success' => true,
            'message' => 'Admin note added successfully.',
            'data' => ['id' => $id],
        ], 201);
    }
}
