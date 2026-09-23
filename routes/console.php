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

Artisan::command('app:assert-database-ready', function (): int {
    $migrator = app('migrator');
    if (! $migrator->repositoryExists()) {
        $this->error('Banco sem tabela de migrations. Prepare o schema antes de publicar este código.');

        return 1;
    }

    $applied = $migrator->getRepository()->getRan();
    $paths = array_merge([database_path('migrations')], $migrator->paths());
    $pending = collect($migrator->getMigrationFiles($paths))->keys()
        ->reject(fn (string $migration) => in_array($migration, $applied, true))->values();

    if ($pending->isNotEmpty()) {
        $this->error('O código publicado espera um schema mais novo. Migrations pendentes:');
        $pending->each(fn (string $migration) => $this->line('  - '.$migration));

        return 1;
    }

    $this->info('Schema em dia com as migrations desta versão.');

    return 0;
})->purpose('Refuse to serve when the database is missing migrations this code expects');
