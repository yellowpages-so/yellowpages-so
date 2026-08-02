<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('media.assets')) {
            Schema::create('media.assets', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('uploaded_by')->nullable();
                $table->uuid('business_id')->nullable();
                $table->string('owner_type');
                $table->uuid('owner_id');
                $table->string('collection')->default('default');
                $table->string('disk')->default('public');
                $table->string('path');
                $table->string('original_name');
                $table->string('mime_type');
                $table->string('extension')->nullable();
                $table->unsignedBigInteger('size_bytes');
                $table->string('checksum_sha256', 64);
                $table->unsignedInteger('width')->nullable();
                $table->unsignedInteger('height')->nullable();
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->string('status')->default('ready');
                $table->string('visibility')->default('public');
                $table->string('alt_text')->nullable();
                $table->string('caption')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
                $table->timestampTz('moderated_at')->nullable();
                $table->uuid('moderated_by')->nullable();
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();
                $table->timestampTz('deleted_at')->nullable();

                $table->foreign('uploaded_by')
                    ->references('id')
                    ->on('iam.users')
                    ->nullOnDelete();

                $table->foreign('business_id')
                    ->references('id')
                    ->on('directory.businesses')
                    ->nullOnDelete();

                $table->foreign('moderated_by')
                    ->references('id')
                    ->on('iam.users')
                    ->nullOnDelete();

                $table->index(['owner_type', 'owner_id', 'collection']);
                $table->index(['business_id', 'collection', 'status']);
                $table->index(['status', 'created_at']);
                $table->unique(['disk', 'path']);
            });
        }

        if (! Schema::hasTable('media.asset_variants')) {
            Schema::create('media.asset_variants', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('asset_id');
                $table->string('variant');
                $table->string('disk')->default('public');
                $table->string('path');
                $table->string('mime_type');
                $table->unsignedBigInteger('size_bytes');
                $table->unsignedInteger('width')->nullable();
                $table->unsignedInteger('height')->nullable();
                $table->timestampTz('created_at')->useCurrent();

                $table->foreign('asset_id')
                    ->references('id')
                    ->on('media.assets')
                    ->cascadeOnDelete();

                $table->unique(['asset_id', 'variant']);
            });
        }

        if (! Schema::hasTable('media.asset_tags')) {
            Schema::create('media.asset_tags', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('asset_id');
                $table->string('tag');
                $table->timestampTz('created_at')->useCurrent();

                $table->foreign('asset_id')
                    ->references('id')
                    ->on('media.assets')
                    ->cascadeOnDelete();

                $table->unique(['asset_id', 'tag']);
                $table->index('tag');
            });
        }

        if (! Schema::hasTable('media.processing_jobs')) {
            Schema::create('media.processing_jobs', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('asset_id');
                $table->string('job_type');
                $table->string('status')->default('pending');
                $table->unsignedInteger('attempts')->default(0);
                $table->jsonb('input')->default(DB::raw("'{}'::jsonb"));
                $table->jsonb('output')->nullable();
                $table->text('error_message')->nullable();
                $table->timestampTz('started_at')->nullable();
                $table->timestampTz('completed_at')->nullable();
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();

                $table->foreign('asset_id')
                    ->references('id')
                    ->on('media.assets')
                    ->cascadeOnDelete();

                $table->index(['status', 'created_at']);
            });
        }

        if (! Schema::hasTable('media.access_logs')) {
            Schema::create('media.access_logs', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('asset_id');
                $table->uuid('user_id')->nullable();
                $table->string('action');
                $table->string('ip_hash', 64)->nullable();
                $table->timestampTz('created_at')->useCurrent();

                $table->foreign('asset_id')
                    ->references('id')
                    ->on('media.assets')
                    ->cascadeOnDelete();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('iam.users')
                    ->nullOnDelete();

                $table->index(['asset_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media.access_logs');
        Schema::dropIfExists('media.processing_jobs');
        Schema::dropIfExists('media.asset_tags');
        Schema::dropIfExists('media.asset_variants');
        Schema::dropIfExists('media.assets');
    }
};
