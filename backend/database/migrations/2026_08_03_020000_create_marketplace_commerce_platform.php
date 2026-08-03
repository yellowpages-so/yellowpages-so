<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS commerce');

        if (! Schema::hasTable('commerce.products')) {
            Schema::create('commerce.products', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('business_id');
                $table->uuid('created_by');
                $table->string('type')->default('product');
                $table->string('name');
                $table->string('slug');
                $table->text('short_description')->nullable();
                $table->text('description')->nullable();
                $table->string('sku')->nullable();
                $table->string('status')->default('draft');
                $table->string('currency', 3)->default('USD');
                $table->decimal('price', 14, 2)->default(0);
                $table->decimal('compare_at_price', 14, 2)->nullable();
                $table->boolean('taxable')->default(false);
                $table->boolean('track_inventory')->default(true);
                $table->boolean('digital')->default(false);
                $table->jsonb('attributes')->default(DB::raw("'{}'::jsonb"));
                $table->timestampTz('published_at')->nullable();
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();
                $table->timestampTz('deleted_at')->nullable();

                $table->foreign('business_id')
                    ->references('id')
                    ->on('directory.businesses')
                    ->cascadeOnDelete();

                $table->foreign('created_by')
                    ->references('id')
                    ->on('iam.users')
                    ->cascadeOnDelete();

                $table->unique(['business_id', 'slug']);
                $table->unique(['business_id', 'sku']);
                $table->index(['status', 'published_at']);
                $table->index(['business_id', 'status']);
            });
        }

        if (! Schema::hasTable('commerce.product_categories')) {
            Schema::create('commerce.product_categories', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('business_id')->nullable();
                $table->uuid('parent_id')->nullable();
                $table->string('name');
                $table->string('slug');
                $table->boolean('active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestampTz('created_at')->useCurrent();

                $table->foreign('business_id')
                    ->references('id')
                    ->on('directory.businesses')
                    ->cascadeOnDelete();

                $table->foreign('parent_id')
                    ->references('id')
                    ->on('commerce.product_categories')
                    ->nullOnDelete();

                $table->unique(['business_id', 'slug']);
            });
        }

        if (! Schema::hasTable('commerce.product_category_assignments')) {
            Schema::create('commerce.product_category_assignments', function (Blueprint $table): void {
                $table->uuid('product_id');
                $table->uuid('category_id');

                $table->foreign('product_id')
                    ->references('id')
                    ->on('commerce.products')
                    ->cascadeOnDelete();

                $table->foreign('category_id')
                    ->references('id')
                    ->on('commerce.product_categories')
                    ->cascadeOnDelete();

                $table->primary(['product_id', 'category_id']);
            });
        }

        if (! Schema::hasTable('commerce.inventory')) {
            Schema::create('commerce.inventory', function (Blueprint $table): void {
                $table->uuid('product_id')->primary();
                $table->integer('quantity_on_hand')->default(0);
                $table->integer('quantity_reserved')->default(0);
                $table->integer('reorder_level')->default(0);
                $table->boolean('allow_backorder')->default(false);
                $table->timestampTz('updated_at')->useCurrent();

                $table->foreign('product_id')
                    ->references('id')
                    ->on('commerce.products')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('commerce.carts')) {
            Schema::create('commerce.carts', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('user_id')->nullable();
                $table->string('session_id')->nullable();
                $table->string('currency', 3)->default('USD');
                $table->string('status')->default('active');
                $table->timestampTz('expires_at')->nullable();
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('iam.users')
                    ->nullOnDelete();

                $table->index(['user_id', 'status']);
                $table->index(['session_id', 'status']);
            });
        }

        if (! Schema::hasTable('commerce.cart_items')) {
            Schema::create('commerce.cart_items', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('cart_id');
                $table->uuid('product_id');
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('unit_price', 14, 2);
                $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();

                $table->foreign('cart_id')
                    ->references('id')
                    ->on('commerce.carts')
                    ->cascadeOnDelete();

                $table->foreign('product_id')
                    ->references('id')
                    ->on('commerce.products')
                    ->cascadeOnDelete();

                $table->unique(['cart_id', 'product_id']);
            });
        }

        if (! Schema::hasTable('commerce.wishlists')) {
            Schema::create('commerce.wishlists', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->string('name')->default('Default');
                $table->timestampTz('created_at')->useCurrent();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('iam.users')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('commerce.wishlist_items')) {
            Schema::create('commerce.wishlist_items', function (Blueprint $table): void {
                $table->uuid('wishlist_id');
                $table->uuid('product_id');
                $table->timestampTz('created_at')->useCurrent();

                $table->foreign('wishlist_id')
                    ->references('id')
                    ->on('commerce.wishlists')
                    ->cascadeOnDelete();

                $table->foreign('product_id')
                    ->references('id')
                    ->on('commerce.products')
                    ->cascadeOnDelete();

                $table->primary(['wishlist_id', 'product_id']);
            });
        }

        if (! Schema::hasTable('commerce.coupons')) {
            Schema::create('commerce.coupons', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('business_id')->nullable();
                $table->string('code')->unique();
                $table->string('discount_type');
                $table->decimal('discount_value', 14, 2);
                $table->decimal('minimum_order_total', 14, 2)->nullable();
                $table->unsignedInteger('usage_limit')->nullable();
                $table->unsignedInteger('used_count')->default(0);
                $table->timestampTz('starts_at')->nullable();
                $table->timestampTz('ends_at')->nullable();
                $table->boolean('active')->default(true);
                $table->timestampTz('created_at')->useCurrent();

                $table->foreign('business_id')
                    ->references('id')
                    ->on('directory.businesses')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('commerce.orders')) {
            Schema::create('commerce.orders', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('order_no')->unique();
                $table->uuid('user_id')->nullable();
                $table->uuid('business_id');
                $table->uuid('coupon_id')->nullable();
                $table->string('status')->default('pending');
                $table->string('payment_status')->default('unpaid');
                $table->string('fulfilment_status')->default('unfulfilled');
                $table->string('currency', 3)->default('USD');
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('discount_total', 14, 2)->default(0);
                $table->decimal('tax_total', 14, 2)->default(0);
                $table->decimal('shipping_total', 14, 2)->default(0);
                $table->decimal('grand_total', 14, 2)->default(0);
                $table->string('customer_name');
                $table->string('customer_email')->nullable();
                $table->string('customer_phone')->nullable();
                $table->jsonb('shipping_address')->nullable();
                $table->jsonb('billing_address')->nullable();
                $table->text('notes')->nullable();
                $table->timestampTz('placed_at')->useCurrent();
                $table->timestampTz('cancelled_at')->nullable();
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('iam.users')
                    ->nullOnDelete();

                $table->foreign('business_id')
                    ->references('id')
                    ->on('directory.businesses')
                    ->cascadeOnDelete();

                $table->foreign('coupon_id')
                    ->references('id')
                    ->on('commerce.coupons')
                    ->nullOnDelete();

                $table->index(['business_id', 'status', 'created_at']);
                $table->index(['user_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('commerce.order_items')) {
            Schema::create('commerce.order_items', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('order_id');
                $table->uuid('product_id')->nullable();
                $table->string('product_name');
                $table->string('sku')->nullable();
                $table->unsignedInteger('quantity');
                $table->decimal('unit_price', 14, 2);
                $table->decimal('line_total', 14, 2);
                $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));

                $table->foreign('order_id')
                    ->references('id')
                    ->on('commerce.orders')
                    ->cascadeOnDelete();

                $table->foreign('product_id')
                    ->references('id')
                    ->on('commerce.products')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('commerce.fulfilments')) {
            Schema::create('commerce.fulfilments', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('order_id');
                $table->string('status')->default('pending');
                $table->string('carrier')->nullable();
                $table->string('tracking_number')->nullable();
                $table->timestampTz('shipped_at')->nullable();
                $table->timestampTz('delivered_at')->nullable();
                $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();

                $table->foreign('order_id')
                    ->references('id')
                    ->on('commerce.orders')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('commerce.product_reviews')) {
            Schema::create('commerce.product_reviews', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('product_id');
                $table->uuid('user_id');
                $table->unsignedSmallInteger('rating');
                $table->string('title')->nullable();
                $table->text('body')->nullable();
                $table->string('status')->default('published');
                $table->boolean('verified_purchase')->default(false);
                $table->timestampTz('created_at')->useCurrent();

                $table->foreign('product_id')
                    ->references('id')
                    ->on('commerce.products')
                    ->cascadeOnDelete();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('iam.users')
                    ->cascadeOnDelete();

                $table->unique(['product_id', 'user_id']);
                $table->index(['product_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce.product_reviews');
        Schema::dropIfExists('commerce.fulfilments');
        Schema::dropIfExists('commerce.order_items');
        Schema::dropIfExists('commerce.orders');
        Schema::dropIfExists('commerce.coupons');
        Schema::dropIfExists('commerce.wishlist_items');
        Schema::dropIfExists('commerce.wishlists');
        Schema::dropIfExists('commerce.cart_items');
        Schema::dropIfExists('commerce.carts');
        Schema::dropIfExists('commerce.inventory');
        Schema::dropIfExists('commerce.product_category_assignments');
        Schema::dropIfExists('commerce.product_categories');
        Schema::dropIfExists('commerce.products');
    }
};
