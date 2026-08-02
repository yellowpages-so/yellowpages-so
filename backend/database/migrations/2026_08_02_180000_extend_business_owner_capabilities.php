<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directory.business_profile_progress', function (Blueprint $table): void {
            $table->uuid('business_id')->primary();
            $table->smallInteger('details_score')->default(0);
            $table->smallInteger('contacts_score')->default(0);
            $table->smallInteger('location_score')->default(0);
            $table->smallInteger('services_score')->default(0);
            $table->smallInteger('hours_score')->default(0);
            $table->smallInteger('media_score')->default(0);
            $table->smallInteger('verification_score')->default(0);
            $table->smallInteger('total_score')->default(0);
            $table->jsonb('missing_items')->default(DB::raw("'[]'::jsonb"));
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('business_id')
                ->references('id')
                ->on('directory.businesses')
                ->cascadeOnDelete();
        });

        Schema::create('analytics.business_daily_metrics', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->date('metric_date');
            $table->unsignedBigInteger('profile_views')->default(0);
            $table->unsignedBigInteger('search_impressions')->default(0);
            $table->unsignedBigInteger('website_clicks')->default(0);
            $table->unsignedBigInteger('phone_clicks')->default(0);
            $table->unsignedBigInteger('whatsapp_clicks')->default(0);
            $table->unsignedBigInteger('direction_clicks')->default(0);
            $table->unsignedBigInteger('lead_count')->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('business_id')
                ->references('id')
                ->on('directory.businesses')
                ->cascadeOnDelete();

            $table->unique(['business_id', 'metric_date']);
            $table->index(['business_id', 'metric_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics.business_daily_metrics');
        Schema::dropIfExists('directory.business_profile_progress');
    }
};
