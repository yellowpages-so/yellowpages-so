<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = [
            '# HELP yellowpages_application_up Application availability.',
            '# TYPE yellowpages_application_up gauge',
            'yellowpages_application_up 1',
            '# HELP yellowpages_database_up Database availability.',
            '# TYPE yellowpages_database_up gauge',
            'yellowpages_database_up '.$this->databaseUp(),
            '# HELP yellowpages_businesses_total Total businesses.',
            '# TYPE yellowpages_businesses_total gauge',
            'yellowpages_businesses_total '.$this->count('directory.businesses'),
            '# HELP yellowpages_users_total Total users.',
            '# TYPE yellowpages_users_total gauge',
            'yellowpages_users_total '.$this->count('iam.users'),
        ];

        return response(
            implode(PHP_EOL, $lines).PHP_EOL,
            200,
            ['Content-Type' => 'text/plain; version=0.0.4']
        );
    }

    private function databaseUp(): int
    {
        try {
            DB::select('SELECT 1');

            return 1;
        } catch (\Throwable) {
            return 0;
        }
    }

    private function count(string $table): int
    {
        try {
            return DB::table($table)->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
