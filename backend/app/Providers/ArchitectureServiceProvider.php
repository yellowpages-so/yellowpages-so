<?php

namespace App\Providers;

use App\Domain\Cms\Contracts\PageRepository;
use App\Domain\Cms\Infrastructure\DatabasePageRepository;
use App\Shared\Contracts\AuditLogger;
use App\Shared\Contracts\TransactionManager;
use App\Shared\Infrastructure\DatabaseTransactionManager;
use App\Shared\Infrastructure\LaravelAuditLogger;
use Illuminate\Support\ServiceProvider;

class ArchitectureServiceProvider extends ServiceProvider
{
    public array $bindings = [
        PageRepository::class => DatabasePageRepository::class,

        TransactionManager::class => DatabaseTransactionManager::class,

        AuditLogger::class => LaravelAuditLogger::class,
    ];
}
