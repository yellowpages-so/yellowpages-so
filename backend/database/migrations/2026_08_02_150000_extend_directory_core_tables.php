<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory.categories', function (Blueprint $table): void {
            $table->string('name_so')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->boolean('featured')->default(false);
            $table->jsonb('search_keywords')->default(DB::raw("'[]'::jsonb"));
        });
        Schema::table('directory.services', function (Blueprint $table): void {
            $table->string('name_so')->nullable();
            $table->jsonb('search_keywords')->default(DB::raw("'[]'::jsonb"));
            $table->timestamps();
        });
        DB::statement('ALTER TABLE directory.businesses ADD COLUMN IF NOT EXISTS search_document tsvector');
        DB::statement('CREATE INDEX IF NOT EXISTS businesses_search_document_gin ON directory.businesses USING gin (search_document)');
        DB::statement("CREATE OR REPLACE FUNCTION directory.refresh_business_search_document() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN NEW.search_document := setweight(to_tsvector('simple', coalesce(NEW.trading_name,'')), 'A') || setweight(to_tsvector('simple', coalesce(NEW.legal_name,'')), 'A') || setweight(to_tsvector('simple', coalesce(NEW.short_description,'')), 'B') || setweight(to_tsvector('simple', coalesce(NEW.description,'')), 'C'); RETURN NEW; END; $$");
        DB::statement('DROP TRIGGER IF EXISTS trg_business_search_document ON directory.businesses');
        DB::statement('CREATE TRIGGER trg_business_search_document BEFORE INSERT OR UPDATE OF trading_name,legal_name,short_description,description ON directory.businesses FOR EACH ROW EXECUTE FUNCTION directory.refresh_business_search_document()');
        DB::statement('UPDATE directory.businesses SET trading_name=trading_name');
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_business_search_document ON directory.businesses');
        DB::statement('DROP FUNCTION IF EXISTS directory.refresh_business_search_document()');
        DB::statement('DROP INDEX IF EXISTS directory.businesses_search_document_gin');
        DB::statement('ALTER TABLE directory.businesses DROP COLUMN IF EXISTS search_document');
        Schema::table('directory.services', fn (Blueprint $t) => $t->dropColumn(['name_so', 'search_keywords', 'created_at', 'updated_at']));
        Schema::table('directory.categories', fn (Blueprint $t) => $t->dropColumn(['name_so', 'seo_title', 'seo_description', 'featured', 'search_keywords']));
    }
};
