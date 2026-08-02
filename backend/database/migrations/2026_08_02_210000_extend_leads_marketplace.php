<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leads.quote_requests')) {
            Schema::create('leads.quote_requests', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('reference_no')->unique();
                $table->uuid('customer_user_id')->nullable();
                $table->uuid('category_id')->nullable();
                $table->uuid('city_id')->nullable();
                $table->string('title');
                $table->text('description');
                $table->string('budget_currency', 3)->nullable();
                $table->decimal('budget_min', 14, 2)->nullable();
                $table->decimal('budget_max', 14, 2)->nullable();
                $table->date('required_by')->nullable();
                $table->string('contact_name')->nullable();
                $table->string('contact_email')->nullable();
                $table->string('contact_phone')->nullable();
                $table->string('preferred_contact')->default('email');
                $table->string('status')->default('open');
                $table->smallInteger('lead_score')->default(0);
                $table->timestampTz('expires_at')->nullable();
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();

                $table->foreign('customer_user_id')
                    ->references('id')
                    ->on('iam.users')
                    ->nullOnDelete();

                $table->foreign('category_id')
                    ->references('id')
                    ->on('directory.categories')
                    ->nullOnDelete();

                $table->foreign('city_id')
                    ->references('id')
                    ->on('directory.cities')
                    ->nullOnDelete();

                $table->index(['status', 'created_at']);
                $table->index(['category_id', 'city_id']);
            });
        }

        if (! Schema::hasTable('leads.quote_request_businesses')) {
            Schema::create('leads.quote_request_businesses', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('quote_request_id');
                $table->uuid('business_id');
                $table->string('status')->default('new');
                $table->uuid('assigned_to')->nullable();
                $table->text('business_note')->nullable();
                $table->timestampTz('viewed_at')->nullable();
                $table->timestampTz('responded_at')->nullable();
                $table->timestampTz('closed_at')->nullable();
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();

                $table->foreign('quote_request_id')
                    ->references('id')
                    ->on('leads.quote_requests')
                    ->cascadeOnDelete();

                $table->foreign('business_id')
                    ->references('id')
                    ->on('directory.businesses')
                    ->cascadeOnDelete();

                $table->foreign('assigned_to')
                    ->references('id')
                    ->on('iam.users')
                    ->nullOnDelete();

                $table->unique(['quote_request_id', 'business_id']);
                $table->index(['business_id', 'status', 'created_at']);
            });
        }

        if (! Schema::hasTable('leads.quote_responses')) {
            Schema::create('leads.quote_responses', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('quote_request_id');
                $table->uuid('business_id');
                $table->uuid('user_id');
                $table->text('message');
                $table->string('currency', 3)->nullable();
                $table->decimal('amount', 14, 2)->nullable();
                $table->unsignedInteger('estimated_days')->nullable();
                $table->string('status')->default('submitted');
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();

                $table->foreign('quote_request_id')
                    ->references('id')
                    ->on('leads.quote_requests')
                    ->cascadeOnDelete();

                $table->foreign('business_id')
                    ->references('id')
                    ->on('directory.businesses')
                    ->cascadeOnDelete();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('iam.users')
                    ->cascadeOnDelete();

                $table->index(['quote_request_id', 'created_at']);
                $table->index(['business_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('leads.lead_activity')) {
            Schema::create('leads.lead_activity', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('quote_request_id');
                $table->uuid('business_id')->nullable();
                $table->uuid('actor_user_id')->nullable();
                $table->string('event_type');
                $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
                $table->timestampTz('created_at')->useCurrent();

                $table->foreign('quote_request_id')
                    ->references('id')
                    ->on('leads.quote_requests')
                    ->cascadeOnDelete();

                $table->foreign('business_id')
                    ->references('id')
                    ->on('directory.businesses')
                    ->nullOnDelete();

                $table->foreign('actor_user_id')
                    ->references('id')
                    ->on('iam.users')
                    ->nullOnDelete();

                $table->index(['quote_request_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leads.lead_activity');
        Schema::dropIfExists('leads.quote_responses');
        Schema::dropIfExists('leads.quote_request_businesses');
        Schema::dropIfExists('leads.quote_requests');
    }
};
