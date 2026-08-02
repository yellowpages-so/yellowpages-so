<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ImportSomaliaGeographyTest extends TestCase
{
    public function test_geography_files_pass_validation(): void
    {
        $exitCode = Artisan::call('geography:import', [
            '--path' => '../data/geography/import',
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exitCode);
    }
}
