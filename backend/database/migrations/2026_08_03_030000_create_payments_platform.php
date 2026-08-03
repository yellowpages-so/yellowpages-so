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
        DB::statement('CREATE SCHEMA IF NOT EXISTS payments');

        Schema::create('payments.providers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type');
            $table->boolean('active')->default(true);
            $table->jsonb('capabilities')->default(DB::raw("'[]'::jsonb"));
            $table->jsonb('configuration')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        Schema::create('payments.payment_intents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id')->nullable();
            $table->uuid('order_id')->nullable();
            $table->uuid('invoice_id')->nullable();
            $table->uuid('provider_id');
            $table->string('reference')->unique();
            $table->string('status')->default('requires_payment_method');
            $table->string('currency', 3)->default('USD');
            $table->decimal('amount', 14, 2);
            $table->decimal('captured_amount', 14, 2)->default(0);
            $table->decimal('refunded_amount', 14, 2)->default(0);
            $table->string('provider_reference')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('business_id')
                ->references('id')
                ->on('directory.businesses')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('iam.users')
                ->nullOnDelete();

            $table->foreign('order_id')
                ->references('id')
                ->on('commerce.orders')
                ->nullOnDelete();

            $table->foreign('provider_id')
                ->references('id')
                ->on('payments.providers')
                ->restrictOnDelete();

            $table->index(['business_id', 'status', 'created_at']);
            $table->index(['provider_id', 'status']);
        });

        Schema::create('payments.transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('payment_intent_id');
            $table->string('type');
            $table->string('status')->default('pending');
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3);
            $table->string('provider_transaction_id')->nullable();
            $table->jsonb('provider_response')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('processed_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('payment_intent_id')
                ->references('id')
                ->on('payments.payment_intents')
                ->cascadeOnDelete();

            $table->index(['payment_intent_id', 'type', 'status']);
        });

        Schema::create('payments.refunds', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('payment_intent_id');
            $table->uuid('requested_by')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3);
            $table->string('reason')->nullable();
            $table->string('status')->default('pending');
            $table->string('provider_refund_id')->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('payment_intent_id')
                ->references('id')
                ->on('payments.payment_intents')
                ->cascadeOnDelete();

            $table->foreign('requested_by')
                ->references('id')
                ->on('iam.users')
                ->nullOnDelete();
        });

        Schema::create('payments.splits', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('payment_intent_id');
            $table->uuid('business_id');
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3);
            $table->string('status')->default('pending');
            $table->timestampTz('released_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('payment_intent_id')
                ->references('id')
                ->on('payments.payment_intents')
                ->cascadeOnDelete();

            $table->foreign('business_id')
                ->references('id')
                ->on('directory.businesses')
                ->cascadeOnDelete();

            $table->index(['business_id', 'status']);
        });

        Schema::create('payments.escrows', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('payment_intent_id');
            $table->uuid('business_id');
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3);
            $table->string('status')->default('held');
            $table->string('release_condition')->nullable();
            $table->timestampTz('release_due_at')->nullable();
            $table->timestampTz('released_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('payment_intent_id')
                ->references('id')
                ->on('payments.payment_intents')
                ->cascadeOnDelete();

            $table->foreign('business_id')
                ->references('id')
                ->on('directory.businesses')
                ->cascadeOnDelete();
        });

        Schema::create('payments.webhook_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('provider_id');
            $table->string('event_type');
            $table->string('provider_event_id')->nullable();
            $table->jsonb('payload');
            $table->string('status')->default('received');
            $table->timestampTz('processed_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('provider_id')
                ->references('id')
                ->on('payments.providers')
                ->cascadeOnDelete();

            $table->unique(['provider_id', 'provider_event_id']);
        });

        Schema::create('payments.reconciliation_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('provider_id');
            $table->date('reconciliation_date');
            $table->decimal('expected_amount', 14, 2)->default(0);
            $table->decimal('reported_amount', 14, 2)->default(0);
            $table->decimal('difference_amount', 14, 2)->default(0);
            $table->string('status')->default('pending');
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('provider_id')
                ->references('id')
                ->on('payments.providers')
                ->cascadeOnDelete();

            $table->unique(['provider_id', 'reconciliation_date']);
        });

        foreach ([
            ['stripe', 'Stripe', 'card'],
            ['paypal', 'PayPal', 'wallet'],
            ['evc_plus', 'EVC Plus', 'mobile_money'],
            ['zaad', 'Zaad', 'mobile_money'],
            ['sahal', 'Sahal', 'mobile_money'],
            ['edahab', 'eDahab', 'mobile_money'],
            ['mpesa', 'M-Pesa', 'mobile_money'],
            ['premier_bank', 'Premier Bank', 'bank'],
            ['salaam_bank', 'Salaam Bank', 'bank'],
            ['manual', 'Manual Payment', 'manual'],
        ] as [$code, $name, $type]) {
            DB::table('payments.providers')->updateOrInsert(
                ['code' => $code],
                [
                    'id' => DB::table('payments.providers')
                        ->where('code', $code)
                        ->value('id') ?? (string) Str::uuid(),
                    'name' => $name,
                    'type' => $type,
                    'active' => true,
                    'capabilities' => json_encode([
                        'payment_intents',
                        'refunds',
                        'webhooks',
                    ]),
                    'configuration' => json_encode([]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments.reconciliation_records');
        Schema::dropIfExists('payments.webhook_events');
        Schema::dropIfExists('payments.escrows');
        Schema::dropIfExists('payments.splits');
        Schema::dropIfExists('payments.refunds');
        Schema::dropIfExists('payments.transactions');
        Schema::dropIfExists('payments.payment_intents');
        Schema::dropIfExists('payments.providers');
    }
};
