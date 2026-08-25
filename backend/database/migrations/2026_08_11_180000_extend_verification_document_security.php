<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification.verification_documents', function (Blueprint $table): void {
            if (! Schema::hasColumn('verification.verification_documents', 'virus_scan_status')) {
                $table->string('virus_scan_status')
                    ->default('pending');
            }

            if (! Schema::hasColumn('verification.verification_documents', 'virus_scanned_at')) {
                $table->timestampTz('virus_scanned_at')
                    ->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('verification.verification_documents', function (Blueprint $table): void {
            if (Schema::hasColumn('verification.verification_documents', 'virus_scanned_at')) {
                $table->dropColumn('virus_scanned_at');
            }

            if (Schema::hasColumn('verification.verification_documents', 'virus_scan_status')) {
                $table->dropColumn('virus_scan_status');
            }
        });
    }
};
