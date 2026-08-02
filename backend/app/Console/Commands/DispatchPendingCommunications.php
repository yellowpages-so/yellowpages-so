<?php

namespace App\Console\Commands;

use App\Jobs\DeliverCommunication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DispatchPendingCommunications extends Command
{
    protected $signature = 'communications:dispatch-pending
        {--limit=100}';

    protected $description = 'Dispatch pending communication messages';

    public function handle(): int
    {
        $limit = max((int) $this->option('limit'), 1);

        $messages = DB::table('notifications.messages')
            ->where('status', 'pending')
            ->where(function ($query): void {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->orderBy('created_at')
            ->limit($limit)
            ->get(['id']);

        foreach ($messages as $message) {
            DeliverCommunication::dispatch($message->id);
        }

        $this->info(
            "Dispatched {$messages->count()} communication messages."
        );

        return self::SUCCESS;
    }
}
