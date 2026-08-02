<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reviews.review_replies')) {
            Schema::create('reviews.review_replies', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('review_id')->unique();
                $table->uuid('business_id');
                $table->uuid('user_id');
                $table->text('reply');
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();

                $table->foreign('review_id')->references('id')->on('reviews.reviews')->cascadeOnDelete();
                $table->foreign('business_id')->references('id')->on('directory.businesses')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('iam.users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('reviews.review_helpful_votes')) {
            Schema::create('reviews.review_helpful_votes', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('review_id');
                $table->uuid('user_id');
                $table->boolean('helpful')->default(true);
                $table->timestampTz('created_at')->useCurrent();

                $table->foreign('review_id')->references('id')->on('reviews.reviews')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('iam.users')->cascadeOnDelete();
                $table->unique(['review_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('reviews.review_moderation_events')) {
            Schema::create('reviews.review_moderation_events', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('review_id');
                $table->uuid('actor_user_id')->nullable();
                $table->string('action');
                $table->string('reason_code')->nullable();
                $table->text('notes')->nullable();
                $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
                $table->timestampTz('created_at')->useCurrent();

                $table->foreign('review_id')->references('id')->on('reviews.reviews')->cascadeOnDelete();
                $table->foreign('actor_user_id')->references('id')->on('iam.users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('reviews.business_reputation_scores')) {
            Schema::create('reviews.business_reputation_scores', function (Blueprint $table): void {
                $table->uuid('business_id')->primary();
                $table->decimal('score', 5, 2)->default(0);
                $table->decimal('average_rating', 3, 2)->default(0);
                $table->unsignedInteger('review_count')->default(0);
                $table->unsignedInteger('verified_review_count')->default(0);
                $table->unsignedInteger('helpful_vote_count')->default(0);
                $table->timestampTz('updated_at')->useCurrent();

                $table->foreign('business_id')->references('id')->on('directory.businesses')->cascadeOnDelete();
            });
        }

        Schema::table('reviews.reviews', function (Blueprint $table): void {
            if (! Schema::hasColumn('reviews.reviews', 'status')) {
                $table->string('status')->default('published');
            }
            if (! Schema::hasColumn('reviews.reviews', 'verified_customer')) {
                $table->boolean('verified_customer')->default(false);
            }
            if (! Schema::hasColumn('reviews.reviews', 'helpful_count')) {
                $table->unsignedInteger('helpful_count')->default(0);
            }
            if (! Schema::hasColumn('reviews.reviews', 'moderation_score')) {
                $table->smallInteger('moderation_score')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews.business_reputation_scores');
        Schema::dropIfExists('reviews.review_moderation_events');
        Schema::dropIfExists('reviews.review_helpful_votes');
        Schema::dropIfExists('reviews.review_replies');
    }
};
