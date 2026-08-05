<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'CREATE SCHEMA IF NOT EXISTS cms'
        );

        Schema::create('cms.seo_metadata', function (
            Blueprint $table
        ): void {
            $table->uuid('id')->primary();

            $table->string('content_type', 50);
            $table->uuid('content_id');

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('canonical_url')->nullable();

            $table->string('robots')->default(
                'index,follow'
            );

            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->text('og_image')->nullable();

            $table->string('twitter_card')->default(
                'summary_large_image'
            );

            $table->jsonb('structured_data')->default(
                DB::raw("'{}'::jsonb")
            );

            $table->timestampsTz();

            $table->unique(
                ['content_type', 'content_id'],
                'cms_seo_metadata_content_unique'
            );

            $table->index(
                'content_id',
                'cms_seo_metadata_content_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms.seo_metadata');
    }
};
