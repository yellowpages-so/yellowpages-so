<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CommerceService
{
    public function createProduct(User $user, array $data): string
    {
        $this->assertManager($user, $data['business_id']);

        return DB::transaction(function () use ($user, $data): string {
            $id = (string) Str::uuid();

            DB::table('commerce.products')->insert([
                'id' => $id,
                'business_id' => $data['business_id'],
                'created_by' => $user->id,
                'type' => $data['type'],
                'name' => $data['name'],
                'slug' => $data['slug'],
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? null,
                'sku' => $data['sku'] ?? null,
                'status' => 'draft',
                'currency' => strtoupper($data['currency']),
                'price' => $data['price'],
                'compare_at_price' => $data['compare_at_price'] ?? null,
                'taxable' => $data['taxable'] ?? false,
                'track_inventory' => $data['track_inventory'] ?? true,
                'digital' => $data['digital'] ?? ($data['type'] === 'digital'),
                'attributes' => json_encode($data['attributes'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('commerce.inventory')->insert([
                'product_id' => $id,
                'quantity_on_hand' => $data['quantity_on_hand'] ?? 0,
                'quantity_reserved' => 0,
                'reorder_level' => 0,
                'allow_backorder' => $data['allow_backorder'] ?? false,
                'updated_at' => now(),
            ]);

            return $id;
        });
    }

    public function addToCart(
        ?User $user,
        string $productId,
        int $quantity,
        ?string $sessionId
    ): array {
        $product = DB::table('commerce.products')
            ->where('id', $productId)
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->first();

        if (! $product) {
            throw new RuntimeException('Product not found.');
        }

        $inventory = DB::table('commerce.inventory')
            ->where('product_id', $productId)
            ->first();

        if (
            $product->track_inventory
            && $inventory
            && ! $inventory->allow_backorder
            && (($inventory->quantity_on_hand - $inventory->quantity_reserved) < $quantity)
        ) {
            throw new RuntimeException('Insufficient inventory.');
        }

        return DB::transaction(function () use (
            $user,
            $sessionId,
            $product,
            $quantity
        ): array {
            $cart = DB::table('commerce.carts')
                ->when(
                    $user,
                    fn ($query) => $query->where('user_id', $user->id),
                    fn ($query) => $query->where('session_id', $sessionId)
                )
                ->where('status', 'active')
                ->first();

            if (! $cart) {
                $cartId = (string) Str::uuid();

                DB::table('commerce.carts')->insert([
                    'id' => $cartId,
                    'user_id' => $user?->id,
                    'session_id' => $sessionId,
                    'currency' => $product->currency,
                    'status' => 'active',
                    'expires_at' => now()->addDays(14),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $cartId = $cart->id;
            }

            $existing = DB::table('commerce.cart_items')
                ->where('cart_id', $cartId)
                ->where('product_id', $product->id)
                ->first();

            DB::table('commerce.cart_items')->updateOrInsert(
                [
                    'cart_id' => $cartId,
                    'product_id' => $product->id,
                ],
                [
                    'id' => $existing->id ?? (string) Str::uuid(),
                    'quantity' => ($existing->quantity ?? 0) + $quantity,
                    'unit_price' => $product->price,
                    'metadata' => json_encode([]),
                    'created_at' => $existing->created_at ?? now(),
                    'updated_at' => now(),
                ]
            );

            return $this->cart($cartId);
        });
    }

    public function cart(string $cartId): array
    {
        $cart = DB::table('commerce.carts')
            ->where('id', $cartId)
            ->first();

        if (! $cart) {
            throw new RuntimeException('Cart not found.');
        }

        $items = DB::table('commerce.cart_items as items')
            ->join('commerce.products as products', 'products.id', '=', 'items.product_id')
            ->where('items.cart_id', $cartId)
            ->get([
                'items.id',
                'items.product_id',
                'items.quantity',
                'items.unit_price',
                'products.name',
                'products.slug',
                'products.business_id',
            ]);

        return [
            'id' => $cart->id,
            'currency' => $cart->currency,
            'items' => $items,
            'subtotal' => (float) $items->sum(
                fn ($item) => $item->quantity * $item->unit_price
            ),
        ];
    }

    public function checkout(?User $user, array $data): array
    {
        $cart = $this->cart($data['cart_id']);

        if (count($cart['items']) === 0) {
            throw new RuntimeException('Cart is empty.');
        }

        $businessIds = collect($cart['items'])
            ->pluck('business_id')
            ->unique();

        if ($businessIds->count() !== 1) {
            throw new RuntimeException(
                'Checkout currently supports products from one business per order.'
            );
        }

        $businessId = $businessIds->first();
        $coupon = $this->coupon(
            $data['coupon_code'] ?? null,
            $businessId,
            $cart['subtotal']
        );

        $discount = $coupon['discount'] ?? 0;
        $grandTotal = max($cart['subtotal'] - $discount, 0);
        $orderId = (string) Str::uuid();
        $orderNo = 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));

        DB::transaction(function () use (
            $user,
            $data,
            $cart,
            $businessId,
            $coupon,
            $discount,
            $grandTotal,
            $orderId,
            $orderNo
        ): void {
            DB::table('commerce.orders')->insert([
                'id' => $orderId,
                'order_no' => $orderNo,
                'user_id' => $user?->id,
                'business_id' => $businessId,
                'coupon_id' => $coupon['id'] ?? null,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'fulfilment_status' => 'unfulfilled',
                'currency' => $cart['currency'],
                'subtotal' => $cart['subtotal'],
                'discount_total' => $discount,
                'tax_total' => 0,
                'shipping_total' => 0,
                'grand_total' => $grandTotal,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'shipping_address' => isset($data['shipping_address'])
                    ? json_encode($data['shipping_address'])
                    : null,
                'billing_address' => isset($data['billing_address'])
                    ? json_encode($data['billing_address'])
                    : null,
                'notes' => $data['notes'] ?? null,
                'placed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($cart['items'] as $item) {
                DB::table('commerce.order_items')->insert([
                    'id' => (string) Str::uuid(),
                    'order_id' => $orderId,
                    'product_id' => $item->product_id,
                    'product_name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->quantity * $item->unit_price,
                    'metadata' => json_encode([]),
                ]);

                DB::table('commerce.inventory')
                    ->where('product_id', $item->product_id)
                    ->decrement('quantity_on_hand', $item->quantity);
            }

            DB::table('commerce.carts')
                ->where('id', $data['cart_id'])
                ->update([
                    'status' => 'converted',
                    'updated_at' => now(),
                ]);

            if (! empty($coupon['id'])) {
                DB::table('commerce.coupons')
                    ->where('id', $coupon['id'])
                    ->increment('used_count');
            }
        });

        return [
            'id' => $orderId,
            'order_no' => $orderNo,
            'grand_total' => $grandTotal,
            'currency' => $cart['currency'],
            'status' => 'pending',
        ];
    }

    public function ownerOrders(User $user): mixed
    {
        $businessIds = DB::table('directory.business_members')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('business_id');

        return DB::table('commerce.orders')
            ->whereIn('business_id', $businessIds)
            ->orderByDesc('created_at')
            ->paginate(25);
    }

    private function coupon(
        ?string $code,
        string $businessId,
        float $subtotal
    ): array {
        if (! $code) {
            return [];
        }

        $coupon = DB::table('commerce.coupons')
            ->where('code', strtoupper($code))
            ->where('active', true)
            ->where(function ($query) use ($businessId): void {
                $query->whereNull('business_id')
                    ->orWhere('business_id', $businessId);
            })
            ->first();

        if (! $coupon) {
            throw new RuntimeException('Coupon is invalid.');
        }

        if (
            $coupon->minimum_order_total
            && $subtotal < $coupon->minimum_order_total
        ) {
            throw new RuntimeException('Order total is below the coupon minimum.');
        }

        if (
            $coupon->usage_limit
            && $coupon->used_count >= $coupon->usage_limit
        ) {
            throw new RuntimeException('Coupon usage limit reached.');
        }

        $discount = $coupon->discount_type === 'percentage'
            ? $subtotal * ((float) $coupon->discount_value / 100)
            : (float) $coupon->discount_value;

        return [
            'id' => $coupon->id,
            'discount' => min($discount, $subtotal),
        ];
    }

    private function assertManager(User $user, string $businessId): void
    {
        $allowed = DB::table('directory.business_members')
            ->where('business_id', $businessId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (! $allowed) {
            throw new RuntimeException('You do not manage this business.');
        }
    }
}
