<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMediaController extends Controller
{
    public function queue(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => DB::table('media.assets')
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->paginate(50),
        ]);
    }

    public function moderate(
        Request $request,
        string $assetId
    ): JsonResponse {
        AdminAccess::authorize($request->user());

        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject,quarantine'],
        ]);

        $status = match ($data['decision']) {
            'approve' => 'ready',
            'reject' => 'rejected',
            'quarantine' => 'quarantined',
        };

        DB::table('media.assets')
            ->where('id', $assetId)
            ->update([
                'status' => $status,
                'moderated_at' => now(),
                'moderated_by' => $request->user()->id,
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Media moderation decision recorded.',
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'assets' => DB::table('media.assets')
                    ->whereNull('deleted_at')
                    ->count(),
                'storage_bytes' => (int) DB::table('media.assets')
                    ->whereNull('deleted_at')
                    ->sum('size_bytes'),
                'pending_jobs' => DB::table('media.processing_jobs')
                    ->where('status', 'pending')
                    ->count(),
                'quarantined' => DB::table('media.assets')
                    ->where('status', 'quarantined')
                    ->count(),
                'rejected' => DB::table('media.assets')
                    ->where('status', 'rejected')
                    ->count(),
            ],
        ]);
    }
}
