<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSecurityComplianceController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'open_alerts' => DB::table('security.alerts')->where('status', 'open')->count(),
                'failed_logins_24h' => DB::table('security.login_events')
                    ->where('successful', false)
                    ->where('occurred_at', '>=', now()->subDay())
                    ->count(),
                'active_sessions' => DB::table('security.active_sessions')
                    ->whereNull('revoked_at')
                    ->where('expires_at', '>', now())
                    ->count(),
                'open_privacy_requests' => DB::table('compliance.privacy_requests')
                    ->where('status', 'open')
                    ->count(),
            ],
        ]);
    }

    public function alerts(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => DB::table('security.alerts')->orderByDesc('created_at')->paginate(50),
        ]);
    }

    public function auditLogs(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => DB::table('security.audit_logs')->orderByDesc('occurred_at')->paginate(100),
        ]);
    }
}
