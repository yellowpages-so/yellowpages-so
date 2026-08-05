<?php

namespace App\Shared\Infrastructure;

use App\Shared\Contracts\TransactionManager;
use Illuminate\Support\Facades\DB;

class DatabaseTransactionManager implements TransactionManager
{
    public function run(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
