<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications.templates')) {
            Schema::create('notifications.templates', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('channel');
                $table->string('subject')->nullable();
                $table->text('body');
                $table->jsonb('variables')->default(DB::raw("'[]'::jsonb"));
                $table->string('locale')->default('en');
                $table->boolean('active')->default(true);
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('notifications.user_preferences')) {
            Schema::create('notifications.user_preferences', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->string('event_code');
                $table->boolean('email_enabled')->default(true);
                $table->boolean('sms_enabled')->default(false);
                $table->boolean('whatsapp_enabled')->default(false);
                $table->boolean('push_enabled')->default(false);
                $table->boolean('in_app_enabled')->default(true);
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('iam.users')
                    ->cascadeOnDelete();

                $table->unique(['user_id', 'event_code']);
            });
        }

        if (! Schema::hasTable('notifications.messages')) {
            Schema::create('notifications.messages', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('user_id')->nullable();
                $table->uuid('business_id')->nullable();
                $table->string('event_code');
                $table->string('channel');
                $table->string('recipient');
                $table->string('subject')->nullable();
                $table->text('body');
                $table->string('status')->default('pending');
                $table->unsignedInteger('attempts')->default(0);
                $table->unsignedInteger('max_attempts')->default(3);
                $table->string('provider')->nullable();
                $table->string('provider_message_id')->nullable();
                $table->text('last_error')->nullable();
                $table->timestampTz('scheduled_at')->nullable();
                $table->timestampTz('sent_at')->nullable();
                $table->timestampTz('delivered_at')->nullable();
                $table->timestampTz('failed_at')->nullable();
                $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('iam.users')
                    ->nullOnDelete();

                $table->foreign('business_id')
                    ->references('id')
                    ->on('directory.businesses')
                    ->nullOnDelete();

                $table->index(['status', 'scheduled_at']);
                $table->index(['user_id', 'created_at']);
                $table->index(['business_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('notifications.in_app_notifications')) {
            Schema::create('notifications.in_app_notifications', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->string('event_code');
                $table->string('title');
                $table->text('body');
                $table->string('action_url')->nullable();
                $table->string('priority')->default('normal');
                $table->timestampTz('read_at')->nullable();
                $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
                $table->timestampTz('created_at')->useCurrent();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('iam.users')
                    ->cascadeOnDelete();

                $table->index(['user_id', 'read_at', 'created_at']);
            });
        }

        if (! Schema::hasTable('notifications.delivery_events')) {
            Schema::create('notifications.delivery_events', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('message_id');
                $table->string('event_type');
                $table->jsonb('payload')->default(DB::raw("'{}'::jsonb"));
                $table->timestampTz('created_at')->useCurrent();

                $table->foreign('message_id')
                    ->references('id')
                    ->on('notifications.messages')
                    ->cascadeOnDelete();

                $table->index(['message_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('notifications.device_tokens')) {
            Schema::create('notifications.device_tokens', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->string('platform');
                $table->text('token');
                $table->string('device_name')->nullable();
                $table->boolean('active')->default(true);
                $table->timestampTz('last_used_at')->nullable();
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('iam.users')
                    ->cascadeOnDelete();

                $table->unique(['user_id', 'token']);
            });
        }

        foreach ([
            ['lead_received', 'Lead received', 'email', 'New lead received', 'You have received a new lead: {{title}}.'],
            ['verification_update', 'Verification update', 'email', 'Verification status updated', 'Your verification request is now {{status}}.'],
            ['review_received', 'Review received', 'email', 'New customer review', 'Your business received a new {{rating}}-star review.'],
            ['subscription_renewal', 'Subscription renewal', 'email', 'Subscription renewal', 'Your {{plan_name}} plan renews on {{renewal_date}}.'],
            ['invoice_ready', 'Invoice ready', 'email', 'Invoice {{invoice_no}} is ready', 'Your invoice total is {{currency}} {{total}}.'],
            ['campaign_approved', 'Campaign approved', 'email', 'Advertising campaign approved', 'Your campaign {{campaign_name}} is now active.'],
        ] as [$code, $name, $channel, $subject, $body]) {
            DB::table('notifications.templates')->updateOrInsert(
                ['code' => $code, 'channel' => $channel, 'locale' => 'en'],
                [
                    'id' => DB::table('notifications.templates')
                        ->where('code', $code)
                        ->where('channel', $channel)
                        ->where('locale', 'en')
                        ->value('id') ?? (string) Str::uuid(),
                    'name' => $name,
                    'subject' => $subject,
                    'body' => $body,
                    'variables' => json_encode([]),
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications.device_tokens');
        Schema::dropIfExists('notifications.delivery_events');
        Schema::dropIfExists('notifications.in_app_notifications');
        Schema::dropIfExists('notifications.messages');
        Schema::dropIfExists('notifications.user_preferences');
        Schema::dropIfExists('notifications.templates');
    }
};
