<?php

namespace App\Providers;

use App\Domain\Cms\Contracts\PageRepository;
use App\Domain\Cms\Infrastructure\DatabasePageRepository;
use App\Domain\Directory\Contracts\BusinessRepository;
use App\Domain\Directory\Infrastructure\DatabaseBusinessRepository;
use App\Shared\Contracts\AuditLogger;
use App\Shared\Contracts\TransactionManager;
use App\Shared\Infrastructure\DatabaseTransactionManager;
use App\Shared\Infrastructure\LaravelAuditLogger;
use Illuminate\Support\ServiceProvider;

class ArchitectureServiceProvider extends ServiceProvider
{
    public array $bindings = [
        PageRepository::class => DatabasePageRepository::class,

        BusinessRepository::class => DatabaseBusinessRepository::class,

        TransactionManager::class => DatabaseTransactionManager::class,

        AuditLogger::class => LaravelAuditLogger::class,
    ];
}
