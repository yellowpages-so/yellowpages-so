<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SessionSecurityService
{
    public function register(User $user, Request $request): array
    {
        $id = (string) Str::uuid();
        $token = Str::random(64);

        DB::table('security.active_sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'user_agent_hash' => hash('sha256', (string) $request->userAgent()),
            'device_name' => mb_substr((string) $request->userAgent(), 0, 255),
            'last_seen_at' => now(),
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
        ]);

        return ['session_id' => $id, 'session_token' => $token];
    }

    public function revoke(User $user, string $sessionId): void
    {
        DB::table('security.active_sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->update(['revoked_at' => now()]);
    }

    public function revokeAll(User $user): void
    {
        DB::table('security.active_sessions')
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
