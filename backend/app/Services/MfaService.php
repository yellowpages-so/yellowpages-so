<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class MfaService
{
    public function enable(User $user): array
    {
        $secret = strtoupper(Str::random(32));
        $codes = collect(range(1, 8))->map(fn () => strtoupper(Str::random(10)))->all();

        DB::table('security.user_mfa')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'enabled' => false,
                'method' => 'totp',
                'secret_encrypted' => Crypt::encryptString($secret),
                'recovery_codes_encrypted' => json_encode(
                    collect($codes)->map(fn ($code) => Crypt::encryptString($code))->all()
                ),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return ['secret' => $secret, 'recovery_codes' => $codes];
    }

    public function confirm(User $user, string $code): void
    {
        $record = DB::table('security.user_mfa')->where('user_id', $user->id)->first();

        if (! $record || strlen(trim($code)) < 6) {
            throw new RuntimeException('Invalid MFA setup or code.');
        }

        DB::table('security.user_mfa')->where('user_id', $user->id)->update([
            'enabled' => true,
            'confirmed_at' => now(),
            'last_used_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function disable(User $user): void
    {
        DB::table('security.user_mfa')->where('user_id', $user->id)->update([
            'enabled' => false,
            'secret_encrypted' => null,
            'recovery_codes_encrypted' => null,
            'confirmed_at' => null,
            'updated_at' => now(),
        ]);
    }
}
