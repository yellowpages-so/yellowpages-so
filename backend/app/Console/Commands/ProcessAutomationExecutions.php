<?php

namespace App\Console\Commands;

use App\Services\WorkflowAutomationService;
use Illuminate\Console\Command;

class ProcessAutomationExecutions extends Command
{
    protected $signature = 'automation:process
        {--limit=100}';

    protected $description =
        'Process queued workflow executions';

    public function handle(
        WorkflowAutomationService $service
    ): int {
        $count = $service->processPending(
            max((int) $this->option('limit'), 1)
        );

        $this->info(
            "Processed {$count} workflow executions."
        );

        return self::SUCCESS;
    }
}
