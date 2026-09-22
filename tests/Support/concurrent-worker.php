<?php

declare(strict_types=1);

use App\Actions\Tickets\CreateTicket;
use App\Models\Assignee;
use App\Models\Workspace;
use App\Services\Tickets\TicketWriteTransaction;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
[$script, $mode, $database, $signal] = $argv;
if (getenv('DB_CONNECTION') === 'pgsql') {
    if (! preg_match('/^concurrency_[a-f0-9]{16}$/', $database)) {
        throw new RuntimeException('Invalid isolated test schema.');
    }
    config(['database.default' => 'pgsql', 'database.connections.pgsql.search_path' => $database]);
    DB::purge('pgsql');
    if ($mode === 'setup') {
        DB::statement('CREATE SCHEMA "'.$database.'"');
    }
    if ($mode === 'cleanup') {
        DB::statement('DROP SCHEMA IF EXISTS "'.$database.'" CASCADE');
        exit(0);
    }
} else {
    config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database, 'database.connections.sqlite.url' => null]);
    DB::purge('sqlite');
}
if ($mode === 'setup') {
    Artisan::call('migrate', ['--force' => true]);
    $workspace = Workspace::create(['name' => 'Teste concorrente']);
    Assignee::create(['name' => 'Ana', 'workspace_id' => $workspace->id]);
    Assignee::create(['name' => 'Bruno', 'workspace_id' => $workspace->id]);
    exit(0);
}
$data = ['title' => 'Concorrência '.$mode, 'description' => 'Teste de escrita concorrente.', 'priority' => 'medium', 'assignment_mode' => 'automatic'];
if ($mode === 'first') {
    $ticket = app(TicketWriteTransaction::class)->run(function () use ($signal, $data) {
        touch($signal.'.locked');
        $deadline = microtime(true) + 10;
        while (! file_exists($signal.'.release')) {
            if (microtime(true) > $deadline) {
                throw new RuntimeException('Release signal timed out.');
            }
            usleep(10000);
        }

        return app(CreateTicket::class)->execute($data, 1);
    });
} else {
    touch($signal.'.started');
    $ticket = app(CreateTicket::class)->execute($data, 1);
}
echo $ticket->assignee_id;
