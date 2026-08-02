<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPortalCoreTest extends TestCase
{
    public function test_administrator_can_open_dashboard(): void
    {
        $user = User::query()->create([
            'public_id' => 'test-'.Str::ulid(),
            'status' => 'active',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $roleId = DB::table('iam.roles')
            ->where('code', 'administrator')
            ->value('id');

        if (! $roleId) {
            $roleId = (string) Str::uuid();

            DB::table('iam.roles')->insert([
                'id' => $roleId,
                'code' => 'administrator',
                'name' => 'Administrator',
                'scope' => 'platform',
            ]);
        }

        DB::table('iam.user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $roleId,
            'assigned_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true);

        DB::table('iam.user_roles')
            ->where('user_id', $user->id)
            ->delete();

        $user->delete();
    }
}
