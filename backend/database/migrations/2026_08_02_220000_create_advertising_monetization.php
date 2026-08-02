<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertising.placements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('channel');
            $table->string('billing_model')->default('fixed');
            $table->decimal('base_price', 14, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->jsonb('targeting_options')->default(DB::raw("'{}'::jsonb"));
            $table->boolean('active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        Schema::create('advertising.campaigns', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('created_by');
            $table->string('name');
            $table->string('objective')->default('visibility');
            $table->string('billing_model')->default('fixed');
            $table->decimal('total_budget', 14, 2)->default(0);
            $table->decimal('daily_budget', 14, 2)->nullable();
            $table->decimal('spent_amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('draft');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->jsonb('targeting')->default(DB::raw("'{}'::jsonb"));
            $table->text('rejection_reason')->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('business_id')
                ->references('id')
                ->on('directory.businesses')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('iam.users')
                ->cascadeOnDelete();

            $table->foreign('approved_by')
                ->references('id')
                ->on('iam.users')
                ->nullOnDelete();

            $table->index(['business_id', 'status']);
            $table->index(['status', 'starts_on', 'ends_on']);
        });

        Schema::create('advertising.creatives', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id');
            $table->uuid('placement_id');
            $table->string('headline');
            $table->text('body')->nullable();
            $table->string('image_url')->nullable();
            $table->string('destination_url');
            $table->string('call_to_action')->default('Learn more');
            $table->string('status')->default('draft');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('campaign_id')
                ->references('id')
                ->on('advertising.campaigns')
                ->cascadeOnDelete();

            $table->foreign('placement_id')
                ->references('id')
                ->on('advertising.placements')
                ->cascadeOnDelete();

            $table->index(['placement_id', 'status']);
        });

        Schema::create('advertising.events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id');
            $table->uuid('creative_id');
            $table->uuid('business_id');
            $table->string('event_type');
            $table->string('session_id')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->string('page_url')->nullable();
            $table->string('referrer')->nullable();
            $table->decimal('cost_amount', 14, 6)->default(0);
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('campaign_id')
                ->references('id')
                ->on('advertising.campaigns')
                ->cascadeOnDelete();

            $table->foreign('creative_id')
                ->references('id')
                ->on('advertising.creatives')
                ->cascadeOnDelete();

            $table->foreign('business_id')
                ->references('id')
                ->on('directory.businesses')
                ->cascadeOnDelete();

            $table->index(['campaign_id', 'event_type', 'created_at']);
            $table->index(['creative_id', 'created_at']);
        });

        Schema::create('billing.advertising_invoices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('campaign_id')->nullable();
            $table->string('invoice_no')->unique();
            $table->string('status')->default('draft');
            $table->string('currency', 3)->default('USD');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->date('issued_on')->nullable();
            $table->date('due_on')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('business_id')
                ->references('id')
                ->on('directory.businesses')
                ->cascadeOnDelete();

            $table->foreign('campaign_id')
                ->references('id')
                ->on('advertising.campaigns')
                ->nullOnDelete();

            $table->index(['business_id', 'status']);
        });

        foreach ([
            ['homepage_hero', 'Homepage Hero', 'homepage', 'fixed', 250],
            ['homepage_featured', 'Homepage Featured Business', 'homepage', 'fixed', 100],
            ['search_sponsored', 'Sponsored Search Result', 'search', 'cpc', 0.50],
            ['category_banner', 'Category Banner', 'category', 'cpm', 8],
            ['city_banner', 'City Banner', 'city', 'cpm', 6],
            ['business_sidebar', 'Business Profile Sidebar', 'business', 'cpc', 0.35],
        ] as [$code, $name, $channel, $billingModel, $price]) {
            DB::table('advertising.placements')->updateOrInsert(
                ['code' => $code],
                [
                    'id' => DB::table('advertising.placements')
                        ->where('code', $code)
                        ->value('id') ?? (string) Str::uuid(),
                    'name' => $name,
                    'channel' => $channel,
                    'billing_model' => $billingModel,
                    'base_price' => $price,
                    'currency' => 'USD',
                    'targeting_options' => json_encode([
                        'category',
                        'city',
                        'district',
                        'keywords',
                    ]),
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('billing.advertising_invoices');
        Schema::dropIfExists('advertising.events');
        Schema::dropIfExists('advertising.creatives');
        Schema::dropIfExists('advertising.campaigns');
        Schema::dropIfExists('advertising.placements');
    }
};
