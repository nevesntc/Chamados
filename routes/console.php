<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('app:ensure-key', function (): int {
    if (filled(config('app.key'))) {
        $this->info('Application key already configured; preserved.');

        return 0;
    }

    return $this->call('key:generate');
})->purpose('Generate the application key only when missing');

Artisan::command('app:prepare-production-database', function (): int {
    if (config('database.default') !== 'pgsql' || config('database.connections.pgsql.search_path') !== 'chamados') {
        $this->error('This command requires PostgreSQL and the dedicated chamados schema.');

        return 1;
    }
    DB::statement('CREATE SCHEMA IF NOT EXISTS chamados');

    return $this->call('migrate', ['--seed' => true, '--force' => true]);
})->purpose('Prepare the dedicated PostgreSQL schema and run non-destructive migrations');
