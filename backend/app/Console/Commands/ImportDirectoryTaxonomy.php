<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ImportDirectoryTaxonomy extends Command
{
    protected $signature = 'directory:taxonomy-import {--path=../data/directory} {--dry-run}';

    protected $description = 'Import categories and services';

    public function handle(): int
    {
        $p = base_path($this->option('path'));
        $cats = $this->csv("$p/categories.csv");
        $svcs = $this->csv("$p/services.csv");
        if (! $cats || ! $svcs) {
            throw new RuntimeException('CSV files must not be empty.');
        } if ($this->option('dry-run')) {
            $this->table(['Dataset', 'Rows'], [['categories', count($cats)], ['services', count($svcs)]]);

            return self::SUCCESS;
        }
        DB::transaction(function () use ($cats, $svcs) {
            $map = [];
            foreach ($cats as $r) {
                $id = DB::table('directory.categories')->where('slug', $r['slug'])->value('id') ?? (string) Str::uuid();
                $parent = $r['parent_slug'] ? ($map[$r['parent_slug']] ?? DB::table('directory.categories')->where('slug', $r['parent_slug'])->value('id')) : null;
                DB::table('directory.categories')->updateOrInsert(['slug' => $r['slug']], ['id' => $id, 'parent_id' => $parent, 'name' => $r['name_en'], 'name_so' => $r['name_so'], 'description' => $r['description'] ?: null, 'icon' => $r['icon'] ?: null, 'featured' => filter_var($r['featured'], FILTER_VALIDATE_BOOLEAN), 'active' => filter_var($r['active'], FILTER_VALIDATE_BOOLEAN), 'sort_order' => (int) $r['sort_order'], 'search_keywords' => json_encode(array_values(array_filter(array_map('trim', explode('|', $r['keywords']))))), 'seo_title' => $r['name_en'], 'seo_description' => $r['description'] ?: null]);
                $map[$r['slug']] = $id;
            }
            foreach ($svcs as $r) {
                $cid = $map[$r['category_slug']] ?? null;
                if (! $cid) {
                    throw new RuntimeException("Category not found: {$r['category_slug']}");
                }DB::table('directory.services')->updateOrInsert(['slug' => $r['slug']], ['id' => DB::table('directory.services')->where('slug', $r['slug'])->value('id') ?? (string) Str::uuid(), 'category_id' => $cid, 'name' => $r['name_en'], 'name_so' => $r['name_so'], 'description' => $r['description'] ?: null, 'active' => filter_var($r['active'], FILTER_VALIDATE_BOOLEAN), 'search_keywords' => json_encode(array_values(array_filter(array_map('trim', explode('|', $r['keywords']))))), 'created_at' => now(), 'updated_at' => now()]);
            }
        });
        $this->info('Directory taxonomy imported successfully.');

        return self::SUCCESS;
    }

    private function csv(string $f): array
    {
        if (! is_file($f)) {
            throw new RuntimeException("CSV not found: $f");
        }$h = fopen($f, 'r');
        $head = fgetcsv($h);
        $rows = [];
        while (($v = fgetcsv($h)) !== false) {
            if (count($head) !== count($v)) {
                throw new RuntimeException("Invalid CSV row: $f");
            }$rows[] = array_combine($head, $v);
        }fclose($h);

        return $rows;
    }
}
