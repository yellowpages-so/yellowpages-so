<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('developer.api_clients', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('business_id')->nullable();
            $table->uuid('created_by');
            $table->string('name');
            $table->string('environment')->default('production');
            $table->string('status')->default('active');
            $table->string('public_key')->unique();
            $table->string('secret_hash');
            $table->jsonb('scopes')->default(DB::raw("'[]'::jsonb"));
            $table->unsignedInteger('rate_limit_per_minute')->default(60);
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('business_id')
                ->references('id')
                ->on('directory.businesses')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('iam.users')
                ->cascadeOnDelete();

            $table->index(['business_id', 'status']);
            $table->index(['environment', 'status']);
        });

        Schema::create('developer.api_usage', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('api_client_id');
            $table->string('method', 10);
            $table->string('route');
            $table->unsignedSmallInteger('status_code');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('request_bytes')->default(0);
            $table->unsignedInteger('response_bytes')->default(0);
            $table->string('ip_hash', 64)->nullable();
            $table->timestampTz('occurred_at')->useCurrent();

            $table->foreign('api_client_id')
                ->references('id')
                ->on('developer.api_clients')
                ->cascadeOnDelete();

            $table->index(['api_client_id', 'occurred_at']);
            $table->index(['route', 'occurred_at']);
        });

        Schema::create('developer.webhook_subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('api_client_id');
            $table->string('event_code');
            $table->text('endpoint_url');
            $table->string('secret_hash');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('max_attempts')->default(5);
            $table->timestampTz('last_success_at')->nullable();
            $table->timestampTz('last_failure_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('api_client_id')
                ->references('id')
                ->on('developer.api_clients')
                ->cascadeOnDelete();

            $table->unique(['api_client_id', 'event_code', 'endpoint_url']);
            $table->index(['event_code', 'active']);
        });

        Schema::create('developer.webhook_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('subscription_id');
            $table->string('event_code');
            $table->uuid('event_id');
            $table->jsonb('payload');
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->text('last_error')->nullable();
            $table->timestampTz('next_attempt_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('subscription_id')
                ->references('id')
                ->on('developer.webhook_subscriptions')
                ->cascadeOnDelete();

            $table->index(['status', 'next_attempt_at']);
            $table->index(['subscription_id', 'created_at']);
        });

        Schema::create('developer.oauth_clients', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('api_client_id');
            $table->string('client_id')->unique();
            $table->string('client_secret_hash');
            $table->jsonb('redirect_uris')->default(DB::raw("'[]'::jsonb"));
            $table->jsonb('grant_types')->default(DB::raw("'[\"client_credentials\"]'::jsonb"));
            $table->boolean('active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('api_client_id')
                ->references('id')
                ->on('developer.api_clients')
                ->cascadeOnDelete();
        });

        Schema::create('developer.events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('event_code');
            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->jsonb('payload')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('occurred_at')->useCurrent();

            $table->index(['event_code', 'occurred_at']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('developer.events');
        Schema::dropIfExists('developer.oauth_clients');
        Schema::dropIfExists('developer.webhook_deliveries');
        Schema::dropIfExists('developer.webhook_subscriptions');
        Schema::dropIfExists('developer.api_usage');
        Schema::dropIfExists('developer.api_clients');
    }
};
