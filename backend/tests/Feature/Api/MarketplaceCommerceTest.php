<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MarketplaceCommerceTest extends TestCase
{
    public function test_owner_can_create_product(): void
    {
        $user = User::query()->create([
            'public_id' => 'test-'.Str::ulid(),
            'status' => 'active',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $business = Business::query()->create([
            'public_id' => (string) Str::ulid(),
            'legal_name' => 'Commerce Test Limited',
            'trading_name' => 'Commerce Test',
            'slug' => 'commerce-test-'.Str::lower(Str::random(6)),
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

        $response = $this->postJson('/api/v1/commerce/products', [
            'business_id' => $business->id,
            'type' => 'product',
            'name' => 'Test Product',
            'slug' => 'test-product',
            'currency' => 'USD',
            'price' => 25,
            'track_inventory' => true,
            'quantity_on_hand' => 10,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $productId = $response->json('data.id');

        $this->assertDatabaseHas('commerce.products', [
            'id' => $productId,
            'business_id' => $business->id,
        ]);

        DB::table('commerce.product_reviews')->where('product_id', $productId)->delete();
        DB::table('commerce.inventory')->where('product_id', $productId)->delete();
        DB::table('commerce.products')->where('id', $productId)->delete();
        DB::table('directory.business_members')->where('business_id', $business->id)->delete();

        $business->forceDelete();
        $user->delete();
    }
}
