<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS cms');

        Schema::create('cms.pages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('author_id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('template')->default('default');
            $table->string('status')->default('draft');
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->jsonb('blocks')->default(DB::raw("'[]'::jsonb"));
            $table->string('locale', 10)->default('en');
            $table->boolean('is_homepage')->default(false);
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('scheduled_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->timestampTz('deleted_at')->nullable();

            $table->foreign('author_id')
                ->references('id')
                ->on('iam.users')
                ->cascadeOnDelete();

            $table->index(['status', 'published_at']);
            $table->index(['locale', 'status']);
        });

        Schema::create('cms.posts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('author_id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('status')->default('draft');
            $table->string('locale', 10)->default('en');
            $table->uuid('featured_media_id')->nullable();
            $table->boolean('featured')->default(false);
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('scheduled_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->timestampTz('deleted_at')->nullable();

            $table->foreign('author_id')
                ->references('id')
                ->on('iam.users')
                ->cascadeOnDelete();

            $table->index(['status', 'published_at']);
            $table->index(['featured', 'published_at']);
        });

        Schema::create('cms.categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('parent_id')
                ->references('id')
                ->on('cms.categories')
                ->nullOnDelete();
        });

        Schema::create('cms.tags', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('cms.post_categories', function (Blueprint $table): void {
            $table->uuid('post_id');
            $table->uuid('category_id');

            $table->foreign('post_id')
                ->references('id')
                ->on('cms.posts')
                ->cascadeOnDelete();

            $table->foreign('category_id')
                ->references('id')
                ->on('cms.categories')
                ->cascadeOnDelete();

            $table->primary(['post_id', 'category_id']);
        });

        Schema::create('cms.post_tags', function (Blueprint $table): void {
            $table->uuid('post_id');
            $table->uuid('tag_id');

            $table->foreign('post_id')
                ->references('id')
                ->on('cms.posts')
                ->cascadeOnDelete();

            $table->foreign('tag_id')
                ->references('id')
                ->on('cms.tags')
                ->cascadeOnDelete();

            $table->primary(['post_id', 'tag_id']);
        });

        Schema::create('cms.revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('content_type');
            $table->uuid('content_id');
            $table->string('title');
            $table->longText('content')->nullable();
            $table->jsonb('payload')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('user_id')
                ->references('id')
                ->on('iam.users')
                ->cascadeOnDelete();

            $table->index(['content_type', 'content_id', 'created_at']);
        });

        Schema::create('cms.seo_metadata', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('content_type');
            $table->uuid('content_id');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->default('index,follow');
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('twitter_card')->default('summary_large_image');
            $table->jsonb('structured_data')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['content_type', 'content_id']);
        });

        Schema::create('cms.menus', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('location')->unique();
            $table->boolean('active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('cms.menu_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('menu_id');
            $table->uuid('parent_id')->nullable();
            $table->string('label');
            $table->string('url');
            $table->string('target')->default('_self');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);

            $table->foreign('menu_id')
                ->references('id')
                ->on('cms.menus')
                ->cascadeOnDelete();

            $table->foreign('parent_id')
                ->references('id')
                ->on('cms.menu_items')
                ->nullOnDelete();

            $table->index(['menu_id', 'sort_order']);
        });

        Schema::create('cms.banners', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('placement');
            $table->string('headline')->nullable();
            $table->text('body')->nullable();
            $table->string('image_url')->nullable();
            $table->string('link_url')->nullable();
            $table->string('button_label')->nullable();
            $table->string('status')->default('draft');
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index(['placement', 'status', 'starts_at', 'ends_at']);
        });

        Schema::create('cms.landing_pages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('author_id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('landing_type');
            $table->string('status')->default('draft');
            $table->jsonb('blocks')->default(DB::raw("'[]'::jsonb"));
            $table->jsonb('targeting')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('author_id')
                ->references('id')
                ->on('iam.users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms.landing_pages');
        Schema::dropIfExists('cms.banners');
        Schema::dropIfExists('cms.menu_items');
        Schema::dropIfExists('cms.menus');
        Schema::dropIfExists('cms.seo_metadata');
        Schema::dropIfExists('cms.revisions');
        Schema::dropIfExists('cms.post_tags');
        Schema::dropIfExists('cms.post_categories');
        Schema::dropIfExists('cms.tags');
        Schema::dropIfExists('cms.categories');
        Schema::dropIfExists('cms.posts');
        Schema::dropIfExists('cms.pages');
    }
};
