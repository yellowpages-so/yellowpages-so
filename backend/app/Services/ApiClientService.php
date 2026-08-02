<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class ApiClientService
{
    public function create(User $user, array $data): array
    {
        if (! empty($data['business_id'])) {
            $allowed = DB::table('directory.business_members')
                ->where('business_id', $data['business_id'])
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists();

            if (! $allowed) {
                throw new RuntimeException('You do not manage this business.');
            }
        }

        $id = (string) Str::uuid();
        $publicKey = 'yp_'.Str::lower(Str::random(24));
        $secret = 'yps_'.Str::random(48);

        DB::table('developer.api_clients')->insert([
            'id' => $id,
            'business_id' => $data['business_id'] ?? null,
            'created_by' => $user->id,
            'name' => $data['name'],
            'environment' => $data['environment'],
            'status' => 'active',
            'public_key' => $publicKey,
            'secret_hash' => Hash::make($secret),
            'scopes' => json_encode(array_values(array_unique($data['scopes']))),
            'rate_limit_per_minute' => $data['rate_limit_per_minute']
                ?? config('developer.default_rate_limit'),
            'expires_at' => $data['expires_at'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id' => $id,
            'public_key' => $publicKey,
            'secret' => $secret,
        ];
    }

    public function authenticate(
        string $publicKey,
        string $secret
    ): object {
        $client = DB::table('developer.api_clients')
            ->where('public_key', $publicKey)
            ->where('status', 'active')
            ->first();

        if (! $client || ! Hash::check($secret, $client->secret_hash)) {
            throw new RuntimeException('Invalid API credentials.');
        }

        if ($client->expires_at && now()->greaterThan($client->expires_at)) {
            throw new RuntimeException('API client has expired.');
        }

        DB::table('developer.api_clients')
            ->where('id', $client->id)
            ->update([
                'last_used_at' => now(),
                'updated_at' => now(),
            ]);

        return $client;
    }

    public function rotateSecret(
        User $user,
        string $clientId
    ): array {
        $client = DB::table('developer.api_clients')
            ->where('id', $clientId)
            ->first();

        if (! $client) {
            throw new RuntimeException('API client not found.');
        }

        if ($client->business_id) {
            $allowed = DB::table('directory.business_members')
                ->where('business_id', $client->business_id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists();

            if (! $allowed) {
                throw new RuntimeException('You do not manage this business.');
            }
        } elseif ($client->created_by !== $user->id) {
            throw new RuntimeException('You do not own this API client.');
        }

        $secret = 'yps_'.Str::random(48);

        DB::table('developer.api_clients')
            ->where('id', $clientId)
            ->update([
                'secret_hash' => Hash::make($secret),
                'updated_at' => now(),
            ]);

        return ['secret' => $secret];
    }
}
