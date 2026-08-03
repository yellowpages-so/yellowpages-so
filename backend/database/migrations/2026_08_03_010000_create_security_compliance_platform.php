<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS security');
        DB::statement('CREATE SCHEMA IF NOT EXISTS compliance');

        if (! Schema::hasTable('security.audit_logs')) {
            Schema::create('security.audit_logs', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->uuid('user_id')->nullable();
                $table->uuid('business_id')->nullable();
                $table->string('event_code');
                $table->string('entity_type')->nullable();
                $table->uuid('entity_id')->nullable();
                $table->string('ip_hash', 64)->nullable();
                $table->string('user_agent_hash', 64)->nullable();
                $table->jsonb('before')->nullable();
                $table->jsonb('after')->nullable();
                $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
                $table->timestampTz('occurred_at')->useCurrent();
                $table->foreign('user_id')->references('id')->on('iam.users')->nullOnDelete();
                $table->foreign('business_id')->references('id')->on('directory.businesses')->nullOnDelete();
                $table->index(['event_code', 'occurred_at']);
                $table->index(['entity_type', 'entity_id']);
            });
        }

        if (! Schema::hasTable('security.login_events')) {
            Schema::create('security.login_events', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('user_id')->nullable();
                $table->string('email')->nullable();
                $table->string('event_type');
                $table->boolean('successful')->default(false);
                $table->string('ip_hash', 64)->nullable();
                $table->string('user_agent_hash', 64)->nullable();
                $table->string('reason')->nullable();
                $table->timestampTz('occurred_at')->useCurrent();
                $table->foreign('user_id')->references('id')->on('iam.users')->nullOnDelete();
                $table->index(['successful', 'occurred_at']);
            });
        }

        if (! Schema::hasTable('security.user_mfa')) {
            Schema::create('security.user_mfa', function (Blueprint $table): void {
                $table->uuid('user_id')->primary();
                $table->boolean('enabled')->default(false);
                $table->string('method')->default('totp');
                $table->text('secret_encrypted')->nullable();
                $table->jsonb('recovery_codes_encrypted')->nullable();
                $table->timestampTz('confirmed_at')->nullable();
                $table->timestampTz('last_used_at')->nullable();
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();
                $table->foreign('user_id')->references('id')->on('iam.users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('security.active_sessions')) {
            Schema::create('security.active_sessions', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->string('token_hash', 64)->unique();
                $table->string('ip_hash', 64)->nullable();
                $table->string('user_agent_hash', 64)->nullable();
                $table->string('device_name')->nullable();
                $table->timestampTz('last_seen_at')->useCurrent();
                $table->timestampTz('expires_at');
                $table->timestampTz('revoked_at')->nullable();
                $table->timestampTz('created_at')->useCurrent();
                $table->foreign('user_id')->references('id')->on('iam.users')->cascadeOnDelete();
                $table->index(['user_id', 'revoked_at', 'expires_at']);
            });
        }

        if (! Schema::hasTable('security.alerts')) {
            Schema::create('security.alerts', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('alert_type');
                $table->string('severity')->default('medium');
                $table->uuid('user_id')->nullable();
                $table->uuid('business_id')->nullable();
                $table->string('status')->default('open');
                $table->string('title');
                $table->text('description');
                $table->jsonb('evidence')->default(DB::raw("'{}'::jsonb"));
                $table->uuid('resolved_by')->nullable();
                $table->timestampTz('resolved_at')->nullable();
                $table->timestampTz('created_at')->useCurrent();
                $table->foreign('user_id')->references('id')->on('iam.users')->nullOnDelete();
                $table->foreign('business_id')->references('id')->on('directory.businesses')->nullOnDelete();
                $table->foreign('resolved_by')->references('id')->on('iam.users')->nullOnDelete();
                $table->index(['status', 'severity', 'created_at']);
            });
        }

        if (! Schema::hasTable('compliance.privacy_requests')) {
            Schema::create('compliance.privacy_requests', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('user_id')->nullable();
                $table->string('request_type');
                $table->string('status')->default('open');
                $table->string('email');
                $table->text('details')->nullable();
                $table->uuid('assigned_to')->nullable();
                $table->timestampTz('verified_at')->nullable();
                $table->timestampTz('completed_at')->nullable();
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();
                $table->foreign('user_id')->references('id')->on('iam.users')->nullOnDelete();
                $table->foreign('assigned_to')->references('id')->on('iam.users')->nullOnDelete();
                $table->index(['status', 'request_type', 'created_at']);
            });
        }

        if (! Schema::hasTable('compliance.retention_policies')) {
            Schema::create('compliance.retention_policies', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('data_category')->unique();
                $table->unsignedInteger('retention_days');
                $table->string('action')->default('delete');
                $table->boolean('active')->default(true);
                $table->text('legal_basis')->nullable();
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('compliance.backup_records')) {
            Schema::create('compliance.backup_records', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('backup_type');
                $table->string('storage_location');
                $table->string('checksum_sha256', 64)->nullable();
                $table->unsignedBigInteger('size_bytes')->nullable();
                $table->string('status')->default('completed');
                $table->timestampTz('started_at')->nullable();
                $table->timestampTz('completed_at')->nullable();
                $table->timestampTz('verified_at')->nullable();
                $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
                $table->timestampTz('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance.backup_records');
        Schema::dropIfExists('compliance.retention_policies');
        Schema::dropIfExists('compliance.privacy_requests');
        Schema::dropIfExists('security.alerts');
        Schema::dropIfExists('security.active_sessions');
        Schema::dropIfExists('security.user_mfa');
        Schema::dropIfExists('security.login_events');
        Schema::dropIfExists('security.audit_logs');
    }
};
