<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory.business_claims', function (Blueprint $table): void {
            $table->string('claim_type')->default('ownership');
            $table->text('claim_reason')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('decided_at')->nullable();
        });

        DB::statement(
            'ALTER TABLE directory.business_claims
             ADD CONSTRAINT business_claims_assigned_to_fkey
             FOREIGN KEY (assigned_to)
             REFERENCES iam.users(id)'
        );

        Schema::table('verification.verification_requests', function (Blueprint $table): void {
            $table->uuid('claim_id')->nullable();
            $table->string('reference_no')->nullable()->unique();
            $table->string('current_step')->default('submitted');
            $table->smallInteger('risk_score')->default(0);
            $table->timestampTz('expires_at')->nullable();
            $table->text('rejection_reason')->nullable();
        });

        DB::statement(
            'ALTER TABLE verification.verification_requests
             ADD CONSTRAINT verification_requests_claim_id_fkey
             FOREIGN KEY (claim_id)
             REFERENCES directory.business_claims(id)
             ON DELETE SET NULL'
        );

        Schema::table('verification.verification_documents', function (Blueprint $table): void {
            $table->uuid('uploaded_by')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum_sha256', 64)->nullable();
            $table->boolean('virus_scan_passed')->default(false);
            $table->timestampTz('reviewed_at')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->text('review_notes')->nullable();
        });

        DB::statement(
            'ALTER TABLE verification.verification_documents
             ADD CONSTRAINT verification_documents_uploaded_by_fkey
             FOREIGN KEY (uploaded_by)
             REFERENCES iam.users(id)'
        );

        DB::statement(
            'ALTER TABLE verification.verification_documents
             ADD CONSTRAINT verification_documents_reviewed_by_fkey
             FOREIGN KEY (reviewed_by)
             REFERENCES iam.users(id)'
        );

        Schema::create('verification.verification_history', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('request_id')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->string('event_type');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('business_id')
                ->references('id')
                ->on('directory.businesses')
                ->cascadeOnDelete();

            $table->foreign('request_id')
                ->references('id')
                ->on('verification.verification_requests')
                ->nullOnDelete();

            $table->foreign('actor_user_id')
                ->references('id')
                ->on('iam.users')
                ->nullOnDelete();

            $table->index(['business_id', 'created_at']);
            $table->index(['request_id', 'created_at']);
        });

        DB::table('verification.verification_levels')->updateOrInsert(
            ['code' => 'unverified'],
            [
                'name' => 'Unverified',
                'rank' => 0,
                'description' => 'No verification checks completed.',
            ]
        );

        DB::table('verification.verification_levels')->updateOrInsert(
            ['code' => 'contact_verified'],
            [
                'name' => 'Contact Verified',
                'rank' => 1,
                'description' => 'Email or phone ownership verified.',
            ]
        );

        DB::table('verification.verification_levels')->updateOrInsert(
            ['code' => 'document_verified'],
            [
                'name' => 'Document Verified',
                'rank' => 2,
                'description' => 'Business documents reviewed and accepted.',
            ]
        );

        DB::table('verification.verification_levels')->updateOrInsert(
            ['code' => 'location_verified'],
            [
                'name' => 'Location Verified',
                'rank' => 3,
                'description' => 'Physical business location verified.',
            ]
        );

        DB::table('verification.verification_levels')->updateOrInsert(
            ['code' => 'trusted_business'],
            [
                'name' => 'Trusted Business',
                'rank' => 4,
                'description' => 'Full verification completed.',
            ]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('verification.verification_history');

        DB::statement(
            'ALTER TABLE verification.verification_documents
             DROP CONSTRAINT IF EXISTS verification_documents_uploaded_by_fkey'
        );

        DB::statement(
            'ALTER TABLE verification.verification_documents
             DROP CONSTRAINT IF EXISTS verification_documents_reviewed_by_fkey'
        );

        Schema::table('verification.verification_documents', function (Blueprint $table): void {
            $table->dropColumn([
                'uploaded_by',
                'original_name',
                'mime_type',
                'file_size',
                'checksum_sha256',
                'virus_scan_passed',
                'reviewed_at',
                'reviewed_by',
                'review_notes',
            ]);
        });

        DB::statement(
            'ALTER TABLE verification.verification_requests
             DROP CONSTRAINT IF EXISTS verification_requests_claim_id_fkey'
        );

        Schema::table('verification.verification_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'claim_id',
                'reference_no',
                'current_step',
                'risk_score',
                'expires_at',
                'rejection_reason',
            ]);
        });

        DB::statement(
            'ALTER TABLE directory.business_claims
             DROP CONSTRAINT IF EXISTS business_claims_assigned_to_fkey'
        );

        Schema::table('directory.business_claims', function (Blueprint $table): void {
            $table->dropColumn([
                'claim_type',
                'claim_reason',
                'contact_email',
                'contact_phone',
                'assigned_to',
                'submitted_at',
                'decided_at',
            ]);
        });
    }
};
