<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityComplianceTest extends TestCase
{
    public function test_user_can_start_mfa_setup(): void
    {
        $user = User::query()->create([
            'public_id' => 'test-'.Str::ulid(),
            'status' => 'active',
            'password_hash' => Hash::make('Password123!'),
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/security/mfa/enable')
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('security.user_mfa', ['user_id' => $user->id]);

        DB::table('security.user_mfa')->where('user_id', $user->id)->delete();
        $user->delete();
    }
}
