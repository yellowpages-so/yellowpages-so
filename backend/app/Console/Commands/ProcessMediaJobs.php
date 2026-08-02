<?php

namespace App\Console\Commands;

use App\Services\MediaManagementService;
use Illuminate\Console\Command;

class ProcessMediaJobs extends Command
{
    protected $signature = 'media:process
        {--limit=50}';

    protected $description = 'Process pending media jobs';

    public function handle(
        MediaManagementService $service
    ): int {
        $count = $service->processPending(
            max((int) $this->option('limit'), 1)
        );

        $this->info("Processed {$count} media jobs.");

        return self::SUCCESS;
    }
}
