<?php

namespace App\Console\Commands;

use App\Services\WebhookService;
use Illuminate\Console\Command;

class DeliverWebhooks extends Command
{
    protected $signature = 'developer:deliver-webhooks
        {--limit=100}';

    protected $description = 'Deliver pending developer webhooks';

    public function handle(
        WebhookService $service
    ): int {
        $count = $service->deliverPending(
            max((int) $this->option('limit'), 1)
        );

        $this->info("Processed {$count} webhook deliveries.");

        return self::SUCCESS;
    }
}
