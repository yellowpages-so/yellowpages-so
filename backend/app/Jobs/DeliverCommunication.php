<?php

namespace App\Jobs;

use App\Services\CommunicationManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeliverCommunication implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $messageId
    ) {}

    public function handle(
        CommunicationManager $manager
    ): void {
        $manager->deliver($this->messageId);
    }
}
