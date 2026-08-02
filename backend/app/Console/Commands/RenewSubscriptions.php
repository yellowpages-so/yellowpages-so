<?php

namespace App\Console\Commands;

use App\Services\SubscriptionBillingService;
use Illuminate\Console\Command;

class RenewSubscriptions extends Command
{
    protected $signature = 'billing:renew-subscriptions';

    protected $description = 'Renew subscriptions whose billing period has ended';

    public function handle(
        SubscriptionBillingService $service
    ): int {
        $count = $service->renewDueSubscriptions();

        $this->info("Renewed {$count} subscriptions.");

        return self::SUCCESS;
    }
}
