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
        Schema::create('billing.plans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('billing_interval')->default('month');
            $table->decimal('price', 14, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('trial_days')->default(0);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        Schema::create('billing.features', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('value_type')->default('boolean');
            $table->text('description')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('billing.plan_features', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('plan_id');
            $table->uuid('feature_id');
            $table->string('value')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('plan_id')->references('id')->on('billing.plans')->cascadeOnDelete();
            $table->foreign('feature_id')->references('id')->on('billing.features')->cascadeOnDelete();
            $table->unique(['plan_id', 'feature_id']);
        });

        Schema::create('billing.subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('plan_id');
            $table->uuid('created_by');
            $table->string('status')->default('active');
            $table->timestampTz('trial_ends_at')->nullable();
            $table->timestampTz('current_period_starts_at');
            $table->timestampTz('current_period_ends_at');
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampTz('grace_ends_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->string('payment_provider')->nullable();
            $table->string('provider_subscription_id')->nullable();
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('business_id')->references('id')->on('directory.businesses')->cascadeOnDelete();
            $table->foreign('plan_id')->references('id')->on('billing.plans')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('iam.users')->cascadeOnDelete();

            $table->index(['business_id', 'status']);
            $table->index(['status', 'current_period_ends_at']);
        });

        Schema::create('billing.subscription_usage', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('subscription_id');
            $table->string('feature_code');
            $table->unsignedBigInteger('used_amount')->default(0);
            $table->timestampTz('period_starts_at');
            $table->timestampTz('period_ends_at');
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('subscription_id')->references('id')->on('billing.subscriptions')->cascadeOnDelete();
            $table->unique(['subscription_id', 'feature_code', 'period_starts_at']);
        });

        Schema::create('billing.coupons', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('discount_type');
            $table->decimal('discount_value', 14, 2);
            $table->string('currency', 3)->nullable();
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedInteger('redeemed_count')->default(0);
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('billing.invoices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('subscription_id')->nullable();
            $table->uuid('coupon_id')->nullable();
            $table->string('invoice_no')->unique();
            $table->string('status')->default('draft');
            $table->string('currency', 3)->default('USD');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->date('issued_on')->nullable();
            $table->date('due_on')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('business_id')->references('id')->on('directory.businesses')->cascadeOnDelete();
            $table->foreign('subscription_id')->references('id')->on('billing.subscriptions')->nullOnDelete();
            $table->foreign('coupon_id')->references('id')->on('billing.coupons')->nullOnDelete();
            $table->index(['business_id', 'status']);
        });

        Schema::create('billing.invoice_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('invoice_id')->references('id')->on('billing.invoices')->cascadeOnDelete();
        });

        Schema::create('billing.payments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->uuid('business_id');
            $table->string('provider');
            $table->string('provider_payment_id')->nullable();
            $table->string('status')->default('pending');
            $table->string('currency', 3)->default('USD');
            $table->decimal('amount', 14, 2);
            $table->timestampTz('paid_at')->nullable();
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('invoice_id')->references('id')->on('billing.invoices')->cascadeOnDelete();
            $table->foreign('business_id')->references('id')->on('directory.businesses')->cascadeOnDelete();
            $table->index(['business_id', 'status']);
        });

        Schema::create('billing.subscription_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('subscription_id');
            $table->uuid('actor_user_id')->nullable();
            $table->string('event_type');
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('subscription_id')->references('id')->on('billing.subscriptions')->cascadeOnDelete();
            $table->foreign('actor_user_id')->references('id')->on('iam.users')->nullOnDelete();
            $table->index(['subscription_id', 'created_at']);
        });

        $features = [
            ['branches', 'Branches', 'integer'],
            ['team_members', 'Team members', 'integer'],
            ['monthly_leads', 'Monthly leads', 'integer'],
            ['monthly_ad_credits', 'Monthly advertising credits', 'integer'],
            ['verified_badge', 'Verified badge eligibility', 'boolean'],
            ['priority_search', 'Priority search ranking', 'boolean'],
            ['analytics_access', 'Analytics access', 'boolean'],
            ['api_access', 'API access', 'boolean'],
            ['media_limit', 'Media items', 'integer'],
        ];

        foreach ($features as [$code, $name, $type]) {
            DB::table('billing.features')->updateOrInsert(
                ['code' => $code],
                [
                    'id' => DB::table('billing.features')->where('code', $code)->value('id') ?? (string) Str::uuid(),
                    'name' => $name,
                    'value_type' => $type,
                    'created_at' => now(),
                ]
            );
        }

        $plans = [
            ['free', 'Free', 'month', 0, 0],
            ['starter', 'Starter', 'month', 15, 14],
            ['professional', 'Professional', 'month', 39, 14],
            ['enterprise', 'Enterprise', 'month', 99, 30],
        ];

        foreach ($plans as [$code, $name, $interval, $price, $trial]) {
            DB::table('billing.plans')->updateOrInsert(
                ['code' => $code],
                [
                    'id' => DB::table('billing.plans')->where('code', $code)->value('id') ?? (string) Str::uuid(),
                    'name' => $name,
                    'billing_interval' => $interval,
                    'price' => $price,
                    'currency' => 'USD',
                    'trial_days' => $trial,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $entitlements = [
            'free' => [
                'branches' => '1',
                'team_members' => '1',
                'monthly_leads' => '5',
                'monthly_ad_credits' => '0',
                'verified_badge' => 'false',
                'priority_search' => 'false',
                'analytics_access' => 'false',
                'api_access' => 'false',
                'media_limit' => '5',
            ],
            'starter' => [
                'branches' => '2',
                'team_members' => '3',
                'monthly_leads' => '25',
                'monthly_ad_credits' => '10',
                'verified_badge' => 'true',
                'priority_search' => 'false',
                'analytics_access' => 'true',
                'api_access' => 'false',
                'media_limit' => '25',
            ],
            'professional' => [
                'branches' => '10',
                'team_members' => '10',
                'monthly_leads' => '100',
                'monthly_ad_credits' => '50',
                'verified_badge' => 'true',
                'priority_search' => 'true',
                'analytics_access' => 'true',
                'api_access' => 'false',
                'media_limit' => '100',
            ],
            'enterprise' => [
                'branches' => '9999',
                'team_members' => '9999',
                'monthly_leads' => '9999',
                'monthly_ad_credits' => '250',
                'verified_badge' => 'true',
                'priority_search' => 'true',
                'analytics_access' => 'true',
                'api_access' => 'true',
                'media_limit' => '9999',
            ],
        ];

        foreach ($entitlements as $planCode => $values) {
            $planId = DB::table('billing.plans')->where('code', $planCode)->value('id');

            foreach ($values as $featureCode => $value) {
                $featureId = DB::table('billing.features')->where('code', $featureCode)->value('id');

                DB::table('billing.plan_features')->updateOrInsert(
                    ['plan_id' => $planId, 'feature_id' => $featureId],
                    [
                        'id' => DB::table('billing.plan_features')
                            ->where('plan_id', $planId)
                            ->where('feature_id', $featureId)
                            ->value('id') ?? (string) Str::uuid(),
                        'value' => $value,
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('billing.subscription_events');
        Schema::dropIfExists('billing.payments');
        Schema::dropIfExists('billing.invoice_items');
        Schema::dropIfExists('billing.invoices');
        Schema::dropIfExists('billing.coupons');
        Schema::dropIfExists('billing.subscription_usage');
        Schema::dropIfExists('billing.subscriptions');
        Schema::dropIfExists('billing.plan_features');
        Schema::dropIfExists('billing.features');
        Schema::dropIfExists('billing.plans');
    }
};
