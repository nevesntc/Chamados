<?php

declare(strict_types=1);

namespace Tests\Feature\Tickets;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ConcurrentAssignmentTest extends TestCase
{
    public function test_two_processes_serialize_assignment_on_the_same_database(): void
    {
        $database = tempnam(sys_get_temp_dir(), 'central-concurrency-');
        $signal = $database.'-signal';
        $postgres = getenv('DB_CONNECTION') === 'pgsql';
        $target = $postgres ? 'concurrency_'.bin2hex(random_bytes(8)) : $database;
        $processes = [];
        $worker = function (string $mode) use ($target, $signal, &$processes): Process {
            $command = [PHP_BINARY];
            if (php_ini_loaded_file()) {
                array_push($command, '-c', php_ini_loaded_file());
            }
            array_push($command, __DIR__.'/../../Support/concurrent-worker.php', $mode, $target, $signal);
            $process = new Process($command, dirname(__DIR__, 3), null, null, 20);
            $processes[] = $process;

            return $process;
        };
        try {
            $worker('setup')->mustRun();
            $first = $worker('first');
            $first->start();
            $this->awaitFile($signal.'.locked', $first);
            $second = $worker('second');
            $second->start();
            $this->awaitFile($signal.'.started', $second);
            usleep(150000);
            $this->assertTrue($second->isRunning(), 'Second writer should wait for the first transaction.');
            touch($signal.'.release');
            $first->wait();
            $second->wait();
            $this->assertSame(0, $first->getExitCode(), $first->getErrorOutput().$first->getOutput());
            $this->assertSame(0, $second->getExitCode(), $second->getErrorOutput().$second->getOutput());
            $this->assertSame('1', trim($first->getOutput()));
            $this->assertSame('2', trim($second->getOutput()));
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            if ($postgres) {
                $worker('cleanup')->mustRun();
            }
            foreach ([$database, $database.'-journal', $database.'-wal', $database.'-shm', $signal.'.locked', $signal.'.started', $signal.'.release'] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    private function awaitFile(string $path, Process $process): void
    {
        $deadline = microtime(true) + 10;
        while (! is_file($path)) {
            if (! $process->isRunning() || microtime(true) > $deadline) {
                $this->fail('Worker did not become ready: '.$process->getOutput().$process->getErrorOutput());
            }
            usleep(10000);
        }
    }
}
