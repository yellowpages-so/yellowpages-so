<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system.admin_notes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('actor_user_id')->nullable();
            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->text('note');
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('actor_user_id')
                ->references('id')
                ->on('iam.users')
                ->nullOnDelete();

            $table->index(['entity_type', 'entity_id', 'created_at']);
        });

        Schema::create('system.admin_actions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('actor_user_id')->nullable();
            $table->string('action');
            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->jsonb('payload')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('actor_user_id')
                ->references('id')
                ->on('iam.users')
                ->nullOnDelete();

            $table->index(['entity_type', 'entity_id', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system.admin_actions');
        Schema::dropIfExists('system.admin_notes');
    }
};
