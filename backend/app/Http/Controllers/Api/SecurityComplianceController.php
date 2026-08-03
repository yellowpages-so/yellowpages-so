<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePrivacyRequest;
use App\Services\MfaService;
use App\Services\SessionSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SecurityComplianceController extends Controller
{
    public function __construct(
        private readonly MfaService $mfa,
        private readonly SessionSecurityService $sessions
    ) {}

    public function enableMfa(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->mfa->enable($request->user()),
        ], 201);
    }

    public function confirmMfa(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'min:6', 'max:12']]);
        $this->mfa->confirm($request->user(), $data['code']);

        return response()->json(['success' => true, 'message' => 'MFA enabled successfully.']);
    }

    public function disableMfa(Request $request): JsonResponse
    {
        $this->mfa->disable($request->user());

        return response()->json(['success' => true, 'message' => 'MFA disabled successfully.']);
    }

    public function sessions(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('security.active_sessions')
                ->where('user_id', $request->user()->id)
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function createSession(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->sessions->register($request->user(), $request),
        ], 201);
    }

    public function revokeSession(Request $request, string $sessionId): JsonResponse
    {
        $this->sessions->revoke($request->user(), $sessionId);

        return response()->json(['success' => true, 'message' => 'Session revoked.']);
    }

    public function revokeAllSessions(Request $request): JsonResponse
    {
        $this->sessions->revokeAll($request->user());

        return response()->json(['success' => true, 'message' => 'All sessions revoked.']);
    }

    public function privacyRequest(CreatePrivacyRequest $request): JsonResponse
    {
        $id = (string) Str::uuid();

        DB::table('compliance.privacy_requests')->insert([
            'id' => $id,
            'user_id' => $request->user()?->id,
            'request_type' => $request->validated()['request_type'],
            'status' => 'open',
            'email' => $request->validated()['email'],
            'details' => $request->validated()['details'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Privacy request submitted.',
            'data' => ['id' => $id],
        ], 201);
    }
}
