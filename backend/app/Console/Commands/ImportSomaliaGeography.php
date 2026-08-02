<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ImportSomaliaGeography extends Command
{
    protected $signature = 'geography:import
        {--path=../data/geography/import}
        {--fresh : Remove imported geography before loading}
        {--dry-run : Validate files without writing data}';

    protected $description = 'Import Somalia regions, districts, cities, neighbourhoods and aliases';

    public function handle(): int
    {
        $basePath = base_path($this->option('path'));

        if (! is_dir($basePath)) {
            $this->error("Directory not found: {$basePath}");

            return self::FAILURE;
        }

        $datasets = [
            'regions' => $this->readCsv("{$basePath}/regions.csv"),
            'districts' => $this->readCsv("{$basePath}/districts.csv"),
            'cities' => $this->readCsv("{$basePath}/cities.csv"),
            'neighbourhoods' => $this->readCsv("{$basePath}/neighbourhoods.csv"),
            'aliases' => $this->readCsv("{$basePath}/aliases.csv"),
        ];

        $this->validateDatasets($datasets);

        if ($this->option('dry-run')) {
            $this->table(
                ['Dataset', 'Rows'],
                collect($datasets)
                    ->map(fn (array $rows, string $name): array => [$name, count($rows)])
                    ->values()
                    ->all()
            );

            $this->info('Validation passed. No data was written.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($datasets): void {
            if ($this->option('fresh')) {
                $this->clearImportedData();
            }

            $countryId = $this->importCountry();
            $regions = $this->importRegions($datasets['regions'], $countryId);
            $districts = $this->importDistricts($datasets['districts'], $regions);
            $cities = $this->importCities($datasets['cities'], $regions, $districts);
            $neighbourhoods = $this->importNeighbourhoods(
                $datasets['neighbourhoods'],
                $cities,
                $districts
            );

            $this->importAliases(
                $datasets['aliases'],
                $regions,
                $districts,
                $cities,
                $neighbourhoods
            );
        });

        $this->info('Somalia geography imported successfully.');

        return self::SUCCESS;
    }

    private function importCountry(): string
    {
        $id = DB::table('directory.countries')
            ->where('iso2', 'SO')
            ->value('id');

        if ($id) {
            return $id;
        }

        $id = (string) Str::uuid();

        DB::table('directory.countries')->insert([
            'id' => $id,
            'iso2' => 'SO',
            'iso3' => 'SOM',
            'name' => 'Somalia',
            'active' => true,
        ]);

        return $id;
    }

    private function importRegions(array $rows, string $countryId): array
    {
        $map = [];

        foreach ($rows as $row) {
            $id = DB::table('directory.administrative_areas')
                ->where('code', $row['code'])
                ->value('id') ?? (string) Str::uuid();

            DB::table('directory.administrative_areas')->updateOrInsert(
                ['code' => $row['code']],
                [
                    'id' => $id,
                    'country_id' => $countryId,
                    'parent_id' => null,
                    'area_type' => 'region',
                    'name' => $row['name_en'],
                    'name_so' => $row['name_so'],
                    'slug' => $row['slug'],
                    'latitude' => $this->nullableNumber($row['latitude']),
                    'longitude' => $this->nullableNumber($row['longitude']),
                    'source' => $row['source'],
                    'source_version' => $row['source_version'],
                    'verification_status' => $row['verification_status'],
                    'active' => $this->toBoolean($row['active']),
                ]
            );

            $map[$row['code']] = $id;
        }

        return $map;
    }

    private function importDistricts(array $rows, array $regions): array
    {
        $map = [];

        foreach ($rows as $row) {
            $regionId = $regions[$row['region_code']] ?? null;

            if (! $regionId) {
                throw new RuntimeException("Region not found: {$row['region_code']}");
            }

            $id = DB::table('directory.districts')
                ->where('code', $row['code'])
                ->value('id') ?? (string) Str::uuid();

            DB::table('directory.districts')->updateOrInsert(
                ['code' => $row['code']],
                [
                    'id' => $id,
                    'city_id' => null,
                    'administrative_area_id' => $regionId,
                    'name' => $row['name_en'],
                    'name_so' => $row['name_so'],
                    'slug' => $row['slug'],
                    'latitude' => $this->nullableNumber($row['latitude']),
                    'longitude' => $this->nullableNumber($row['longitude']),
                    'source' => $row['source'],
                    'source_version' => $row['source_version'],
                    'verification_status' => $row['verification_status'],
                    'active' => $this->toBoolean($row['active']),
                ]
            );

            $map[$row['code']] = $id;
        }

        return $map;
    }

    private function importCities(array $rows, array $regions, array $districts): array
    {
        $map = [];

        foreach ($rows as $row) {
            $regionId = $regions[$row['region_code']] ?? null;
            $districtId = $districts[$row['district_code']] ?? null;

            if (! $regionId || ! $districtId) {
                throw new RuntimeException("Invalid parent for city {$row['code']}");
            }

            $id = DB::table('directory.cities')
                ->where('code', $row['code'])
                ->value('id') ?? (string) Str::uuid();

            $latitude = $this->nullableNumber($row['latitude']);
            $longitude = $this->nullableNumber($row['longitude']);

            DB::table('directory.cities')->updateOrInsert(
                ['code' => $row['code']],
                [
                    'id' => $id,
                    'administrative_area_id' => $regionId,
                    'name' => $row['name_en'],
                    'name_so' => $row['name_so'],
                    'slug' => $row['slug'],
                    'location' => $this->pointExpression($latitude, $longitude),
                    'is_capital' => $this->toBoolean($row['is_capital']),
                    'source' => $row['source'],
                    'source_version' => $row['source_version'],
                    'verification_status' => $row['verification_status'],
                    'active' => $this->toBoolean($row['active']),
                ]
            );

            DB::table('directory.districts')
                ->where('id', $districtId)
                ->whereNull('city_id')
                ->update(['city_id' => $id]);

            $map[$row['code']] = $id;
        }

        return $map;
    }

    private function importNeighbourhoods(
        array $rows,
        array $cities,
        array $districts
    ): array {
        $map = [];

        foreach ($rows as $row) {
            $cityId = $cities[$row['city_code']] ?? null;
            $districtId = $districts[$row['district_code']] ?? null;

            if (! $cityId || ! $districtId) {
                throw new RuntimeException("Invalid parent for neighbourhood {$row['code']}");
            }

            $id = DB::table('directory.neighbourhoods')
                ->where('code', $row['code'])
                ->value('id') ?? (string) Str::uuid();

            DB::table('directory.neighbourhoods')->updateOrInsert(
                ['code' => $row['code']],
                [
                    'id' => $id,
                    'district_id' => $districtId,
                    'name' => $row['name_en'],
                    'name_so' => $row['name_so'],
                    'slug' => $row['slug'],
                    'latitude' => $this->nullableNumber($row['latitude']),
                    'longitude' => $this->nullableNumber($row['longitude']),
                    'location_type' => $row['location_type'],
                    'source' => $row['source'],
                    'source_version' => $row['source_version'],
                    'verification_status' => $row['verification_status'],
                    'active' => $this->toBoolean($row['active']),
                ]
            );

            $map[$row['code']] = $id;
        }

        return $map;
    }

    private function importAliases(
        array $rows,
        array $regions,
        array $districts,
        array $cities,
        array $neighbourhoods
    ): void {
        $maps = [
            'region' => $regions,
            'district' => $districts,
            'city' => $cities,
            'neighbourhood' => $neighbourhoods,
        ];

        foreach ($rows as $row) {
            $locationId = $maps[$row['location_type']][$row['location_code']] ?? null;

            if (! $locationId) {
                throw new RuntimeException(
                    "Alias target not found: {$row['location_type']} {$row['location_code']}"
                );
            }

            $existingId = DB::table('directory.location_aliases')
                ->where('location_type', $row['location_type'])
                ->where('location_id', $locationId)
                ->where('alias', $row['alias'])
                ->value('id');

            DB::table('directory.location_aliases')->updateOrInsert(
                [
                    'location_type' => $row['location_type'],
                    'location_id' => $locationId,
                    'alias' => $row['alias'],
                ],
                [
                    'id' => $existingId ?? (string) Str::uuid(),
                    'language' => $row['language'] ?: null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function validateDatasets(array $datasets): void
    {
        foreach ($datasets as $name => $rows) {
            if ($rows === []) {
                throw new RuntimeException("Dataset is empty: {$name}");
            }

            $identifier = $name === 'aliases' ? 'alias' : 'code';
            $values = collect($rows)->pluck($identifier)->filter();

            if ($values->count() !== $values->unique()->count()) {
                throw new RuntimeException("Duplicate identifiers found in {$name}");
            }
        }
    }

    private function readCsv(string $file): array
    {
        if (! is_file($file)) {
            throw new RuntimeException("CSV not found: {$file}");
        }

        $handle = fopen($file, 'r');

        if (! $handle) {
            throw new RuntimeException("Unable to open CSV: {$file}");
        }

        $headers = fgetcsv($handle);

        if (! $headers) {
            fclose($handle);

            return [];
        }

        $rows = [];

        while (($values = fgetcsv($handle)) !== false) {
            if ($values === [null] || $values === []) {
                continue;
            }

            if (count($headers) !== count($values)) {
                fclose($handle);

                throw new RuntimeException("Invalid CSV row in {$file}");
            }

            $rows[] = array_combine($headers, $values);
        }

        fclose($handle);

        return $rows;
    }

    private function nullableNumber(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return (float) $value;
    }

    private function toBoolean(?string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function pointExpression(?float $latitude, ?float $longitude): mixed
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        return DB::raw(
            sprintf(
                'ST_SetSRID(ST_MakePoint(%F, %F), 4326)::geography',
                $longitude,
                $latitude
            )
        );
    }

    private function clearImportedData(): void
    {
        DB::table('directory.location_aliases')->delete();
        DB::table('directory.neighbourhoods')->delete();
        DB::table('directory.districts')->update(['city_id' => null]);
        DB::table('directory.cities')->delete();
        DB::table('directory.districts')->delete();

        DB::table('directory.administrative_areas')
            ->where('area_type', 'region')
            ->delete();
    }
}
