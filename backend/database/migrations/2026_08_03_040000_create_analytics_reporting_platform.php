<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS reporting');

        Schema::create('reporting.events', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('user_id')->nullable();
            $table->uuid('business_id')->nullable();
            $table->string('event_type');
            $table->string('source')->default('web');
            $table->string('session_id')->nullable();
            $table->string('entity_type')->nullable();
            $table->uuid('entity_id')->nullable();
            $table->decimal('value', 14, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->jsonb('dimensions')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('occurred_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('iam.users')->nullOnDelete();
            $table->foreign('business_id')->references('id')->on('directory.businesses')->nullOnDelete();
            $table->index(['business_id', 'event_type', 'occurred_at']);
        });

        Schema::create('reporting.daily_business_metrics', function (Blueprint $table): void {
            $table->uuid('business_id');
            $table->date('metric_date');
            $table->unsignedBigInteger('profile_views')->default(0);
            $table->unsignedBigInteger('search_impressions')->default(0);
            $table->unsignedBigInteger('search_clicks')->default(0);
            $table->unsignedBigInteger('website_clicks')->default(0);
            $table->unsignedBigInteger('phone_clicks')->default(0);
            $table->unsignedBigInteger('direction_clicks')->default(0);
            $table->unsignedBigInteger('lead_count')->default(0);
            $table->unsignedBigInteger('review_count')->default(0);
            $table->decimal('average_rating', 4, 2)->nullable();
            $table->unsignedBigInteger('order_count')->default(0);
            $table->decimal('gross_revenue', 14, 2)->default(0);
            $table->decimal('ad_spend', 14, 2)->default(0);
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('business_id')->references('id')->on('directory.businesses')->cascadeOnDelete();
            $table->primary(['business_id', 'metric_date']);
        });

        Schema::create('reporting.saved_reports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('business_id')->nullable();
            $table->string('name');
            $table->string('report_type');
            $table->jsonb('filters')->default(DB::raw("'{}'::jsonb"));
            $table->jsonb('columns')->default(DB::raw("'[]'::jsonb"));
            $table->string('visualization')->default('table');
            $table->boolean('shared')->default(false);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('iam.users')->cascadeOnDelete();
            $table->foreign('business_id')->references('id')->on('directory.businesses')->nullOnDelete();
        });

        Schema::create('reporting.report_schedules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('saved_report_id');
            $table->string('frequency');
            $table->string('format')->default('csv');
            $table->string('recipient_email');
            $table->boolean('active')->default(true);
            $table->timestampTz('next_run_at')->nullable();
            $table->timestampTz('last_run_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('saved_report_id')->references('id')->on('reporting.saved_reports')->cascadeOnDelete();
        });

        Schema::create('reporting.report_exports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('saved_report_id')->nullable();
            $table->uuid('requested_by');
            $table->string('format');
            $table->string('status')->default('pending');
            $table->string('disk')->default('local');
            $table->string('path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('saved_report_id')->references('id')->on('reporting.saved_reports')->nullOnDelete();
            $table->foreign('requested_by')->references('id')->on('iam.users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporting.report_exports');
        Schema::dropIfExists('reporting.report_schedules');
        Schema::dropIfExists('reporting.saved_reports');
        Schema::dropIfExists('reporting.daily_business_metrics');
        Schema::dropIfExists('reporting.events');
    }
};
