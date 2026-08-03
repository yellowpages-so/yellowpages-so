<?php

namespace App\Console\Commands;

use App\Services\CmsContentService;
use Illuminate\Console\Command;

class PublishScheduledContent extends Command
{
    protected $signature = 'cms:publish-scheduled';

    protected $description = 'Publish scheduled CMS pages and posts';

    public function handle(CmsContentService $service): int
    {
        $count = $service->publishScheduled();

        $this->info("Published {$count} scheduled content items.");

        return self::SUCCESS;
    }
}
