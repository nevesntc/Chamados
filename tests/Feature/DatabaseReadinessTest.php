<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_passes_when_every_migration_is_applied(): void
    {
        $this->artisan('app:assert-database-ready')
            ->expectsOutputToContain('Schema em dia')
            ->assertExitCode(0);
    }

    public function test_it_refuses_when_a_migration_is_missing(): void
    {
        $missing = DB::table('migrations')->orderByDesc('id')->value('migration');
        DB::table('migrations')->where('migration', $missing)->delete();

        $this->artisan('app:assert-database-ready')
            ->expectsOutputToContain('schema mais novo')
            ->expectsOutputToContain($missing)
            ->assertExitCode(1);
    }

    public function test_it_refuses_when_the_database_was_never_prepared(): void
    {
        Schema::drop('migrations');

        $this->artisan('app:assert-database-ready')
            ->expectsOutputToContain('Prepare o schema')
            ->assertExitCode(1);
    }
}
