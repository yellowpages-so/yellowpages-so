<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentsPlatformTest extends TestCase
{
    public function test_user_can_create_manual_payment_intent(): void
    {
        $providerId = DB::table('payments.providers')
            ->where('code', 'manual')
            ->value('id');

        if (! $providerId) {
            $providerId = (string) Str::uuid();

            DB::table('payments.providers')->insert([
                'id' => $providerId,
                'code' => 'manual',
                'name' => 'Manual Payment',
                'type' => 'manual',
                'active' => true,
                'capabilities' => json_encode(['payment_intents']),
                'configuration' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $user = User::query()->create([
            'public_id' => 'test-'.Str::ulid(),
            'status' => 'active',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $business = Business::query()->create([
            'public_id' => (string) Str::ulid(),
            'legal_name' => 'Payments Test Limited',
            'trading_name' => 'Payments Test',
            'slug' => 'payments-test-'.Str::lower(Str::random(6)),
            'status' => 'published',
            'profile_completeness' => 100,
            'created_by' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/payments/intents', [
            'business_id' => $business->id,
            'provider_code' => 'manual',
            'currency' => 'USD',
            'amount' => 25,
            'customer_email' => 'customer@example.com',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $intentId = $response->json('data.id');

        $this->assertDatabaseHas('payments.payment_intents', [
            'id' => $intentId,
            'business_id' => $business->id,
        ]);

        DB::table('payments.transactions')->where('payment_intent_id', $intentId)->delete();
        DB::table('payments.refunds')->where('payment_intent_id', $intentId)->delete();
        DB::table('payments.splits')->where('payment_intent_id', $intentId)->delete();
        DB::table('payments.escrows')->where('payment_intent_id', $intentId)->delete();
        DB::table('payments.payment_intents')->where('id', $intentId)->delete();

        $business->forceDelete();
        $user->delete();
    }
}
