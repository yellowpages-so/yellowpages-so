<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics.search_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->string('query')->nullable();
            $table->jsonb('filters')->nullable();
            $table->unsignedInteger('result_count')->default(0);
            $table->unsignedInteger('processing_time_ms')->nullable();
            $table->string('source')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['query', 'created_at']);
            $table->index(['session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics.search_events');
    }
};
