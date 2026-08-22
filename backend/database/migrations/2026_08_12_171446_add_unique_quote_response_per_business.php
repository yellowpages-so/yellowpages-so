<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads.quote_responses', function (Blueprint $table) {
            $table->unique(
                ['quote_request_id', 'business_id'],
                'quote_responses_request_business_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('leads.quote_responses', function (Blueprint $table) {
            $table->dropUnique(
                'quote_responses_request_business_unique'
            );
        });
    }
};
