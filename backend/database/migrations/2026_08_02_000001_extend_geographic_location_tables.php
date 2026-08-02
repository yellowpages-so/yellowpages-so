<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory.administrative_areas', function (Blueprint $table): void {
            $table->string('code', 20)->nullable()->unique();
            $table->string('name_so')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('source')->nullable();
            $table->string('source_version')->nullable();
            $table->string('verification_status')->default('pending');
        });

        Schema::table('directory.cities', function (Blueprint $table): void {
            $table->string('code', 20)->nullable()->unique();
            $table->string('name_so')->nullable();
            $table->boolean('is_capital')->default(false);
            $table->string('source')->nullable();
            $table->string('source_version')->nullable();
            $table->string('verification_status')->default('pending');
        });

        Schema::table('directory.districts', function (Blueprint $table): void {
            $table->string('code', 30)->nullable()->unique();
            $table->uuid('administrative_area_id')->nullable();
            $table->string('name_so')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('source')->nullable();
            $table->string('source_version')->nullable();
            $table->string('verification_status')->default('pending');
        });

        DB::statement(
            'ALTER TABLE directory.districts
             ADD CONSTRAINT districts_administrative_area_id_fkey
             FOREIGN KEY (administrative_area_id)
             REFERENCES directory.administrative_areas(id)'
        );

        DB::statement(
            'ALTER TABLE directory.districts
             ALTER COLUMN city_id DROP NOT NULL'
        );

        Schema::table('directory.neighbourhoods', function (Blueprint $table): void {
            $table->string('code', 40)->nullable()->unique();
            $table->string('name_so')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('location_type')->default('neighbourhood');
            $table->string('source')->nullable();
            $table->string('source_version')->nullable();
            $table->string('verification_status')->default('pending');
        });

        Schema::create('directory.location_aliases', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('location_type');
            $table->uuid('location_id');
            $table->string('alias');
            $table->string('language', 10)->nullable();
            $table->timestamps();

            $table->unique(['location_type', 'location_id', 'alias']);
            $table->index(['location_type', 'location_id']);
        });

        DB::statement(
            'CREATE INDEX IF NOT EXISTS location_aliases_alias_trgm
             ON directory.location_aliases
             USING gin (alias gin_trgm_ops)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('directory.location_aliases');

        Schema::table('directory.neighbourhoods', function (Blueprint $table): void {
            $table->dropColumn([
                'code', 'name_so', 'latitude', 'longitude',
                'location_type', 'source', 'source_version',
                'verification_status',
            ]);
        });

        DB::statement(
            'ALTER TABLE directory.districts
             DROP CONSTRAINT IF EXISTS districts_administrative_area_id_fkey'
        );

        Schema::table('directory.districts', function (Blueprint $table): void {
            $table->dropColumn([
                'code', 'administrative_area_id', 'name_so',
                'latitude', 'longitude', 'source',
                'source_version', 'verification_status',
            ]);
        });

        Schema::table('directory.cities', function (Blueprint $table): void {
            $table->dropColumn([
                'code', 'name_so', 'is_capital',
                'source', 'source_version',
                'verification_status',
            ]);
        });

        Schema::table('directory.administrative_areas', function (Blueprint $table): void {
            $table->dropColumn([
                'code', 'name_so', 'latitude', 'longitude',
                'source', 'source_version',
                'verification_status',
            ]);
        });
    }
};
