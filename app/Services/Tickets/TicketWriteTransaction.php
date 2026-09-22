<?php

declare(strict_types=1);

namespace App\Services\Tickets;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketWriteTransaction
{
    public function run(Closure $operation): mixed
    {
        try {
            return DB::transaction(function () use ($operation) {
                // The first statement is a write: serialize workload changes before reading counts.
                // Works with PDO SQLite on PHP 8.3, where Laravel's IMMEDIATE option is ignored.
                $locked = DB::table('ticket_write_locks')->where('id', 1)->increment('version');
                if ($locked !== 1) {
                    throw new \LogicException('Missing ticket write lock. Run the database migrations.');
                }

                return $operation();
            }, attempts: 3);
        } catch (QueryException $exception) {
            if (DB::getDriverName() === 'sqlite' && in_array($exception->errorInfo[1] ?? null, [5, 6], true)) {
                throw ValidationException::withMessages(['assignment_mode' => 'A equipe está recebendo outras atualizações. Tente salvar novamente em alguns segundos.']);
            }
            throw $exception;
        }
    }
}
