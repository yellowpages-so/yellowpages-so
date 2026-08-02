<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics.ai_insights', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->string('insight_type');
            $table->text('summary')->nullable();
            $table->jsonb('payload')->default(DB::raw("'{}'::jsonb"));
            $table->decimal('confidence', 5, 4)->nullable();
            $table->string('model_provider')->nullable();
            $table->string('model_name')->nullable();
            $table->string('status')->default('ready');
            $table->timestampTz('generated_at')->useCurrent();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index(['entity_type', 'entity_id', 'insight_type']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('analytics.ai_generation_jobs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('job_type');
            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->uuid('requested_by')->nullable();
            $table->jsonb('input_payload')->default(DB::raw("'{}'::jsonb"));
            $table->jsonb('output_payload')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('requested_by')
                ->references('id')
                ->on('iam.users')
                ->nullOnDelete();

            $table->index(['status', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('analytics.business_recommendations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('recommended_business_id');
            $table->decimal('score', 8, 5);
            $table->jsonb('reasons')->default(DB::raw("'[]'::jsonb"));
            $table->timestampTz('generated_at')->useCurrent();
            $table->timestampTz('expires_at')->nullable();

            $table->foreign('business_id')
                ->references('id')
                ->on('directory.businesses')
                ->cascadeOnDelete();

            $table->foreign('recommended_business_id')
                ->references('id')
                ->on('directory.businesses')
                ->cascadeOnDelete();

            $table->unique(['business_id', 'recommended_business_id']);
            $table->index(['business_id', 'score']);
        });

        Schema::create('moderation.ai_risk_signals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->string('signal_code');
            $table->smallInteger('severity')->default(0);
            $table->decimal('confidence', 5, 4)->nullable();
            $table->jsonb('evidence')->default(DB::raw("'{}'::jsonb"));
            $table->string('status')->default('open');
            $table->uuid('reviewed_by')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('reviewed_by')
                ->references('id')
                ->on('iam.users')
                ->nullOnDelete();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['status', 'severity']);
        });

        Schema::create('analytics.lead_scores', function (Blueprint $table): void {
            $table->uuid('quote_request_id')->primary();
            $table->smallInteger('score')->default(0);
            $table->string('grade')->default('C');
            $table->jsonb('factors')->default(DB::raw("'{}'::jsonb"));
            $table->string('model_provider')->nullable();
            $table->string('model_name')->nullable();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('quote_request_id')
                ->references('id')
                ->on('leads.quote_requests')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics.lead_scores');
        Schema::dropIfExists('moderation.ai_risk_signals');
        Schema::dropIfExists('analytics.business_recommendations');
        Schema::dropIfExists('analytics.ai_generation_jobs');
        Schema::dropIfExists('analytics.ai_insights');
    }
};
