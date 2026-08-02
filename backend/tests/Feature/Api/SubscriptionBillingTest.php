<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionBillingTest extends TestCase
{
    public function test_owner_can_start_subscription(): void
    {
        $user = User::query()->create([
            'public_id' => 'test-'.Str::ulid(),
            'status' => 'active',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $business = Business::query()->create([
            'public_id' => (string) Str::ulid(),
            'legal_name' => 'Billing Test Limited',
            'trading_name' => 'Billing Test',
            'slug' => 'billing-test-'.Str::lower(Str::random(6)),
            'status' => 'published',
            'profile_completeness' => 100,
            'created_by' => $user->id,
        ]);

        DB::table('directory.business_members')->insert([
            'id' => (string) Str::uuid(),
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role_code' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
            'created_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/billing/subscriptions', [
            'business_id' => $business->id,
            'plan_code' => 'starter',
            'payment_provider' => 'manual',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $subscriptionId = $response->json('data.subscription_id');

        $this->assertDatabaseHas('billing.subscriptions', [
            'id' => $subscriptionId,
            'business_id' => $business->id,
        ]);

        DB::table('billing.subscription_events')
            ->where('subscription_id', $subscriptionId)
            ->delete();

        $invoiceIds = DB::table('billing.invoices')
            ->where('subscription_id', $subscriptionId)
            ->pluck('id');

        DB::table('billing.invoice_items')
            ->whereIn('invoice_id', $invoiceIds)
            ->delete();

        DB::table('billing.payments')
            ->whereIn('invoice_id', $invoiceIds)
            ->delete();

        DB::table('billing.invoices')
            ->whereIn('id', $invoiceIds)
            ->delete();

        DB::table('billing.subscription_usage')
            ->where('subscription_id', $subscriptionId)
            ->delete();

        DB::table('billing.subscriptions')
            ->where('id', $subscriptionId)
            ->delete();

        DB::table('directory.business_members')
            ->where('business_id', $business->id)
            ->delete();

        $business->forceDelete();
        $user->delete();
    }
}
