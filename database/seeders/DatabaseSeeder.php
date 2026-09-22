<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Assignee;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Ana Martins', 'Bruno Costa', 'Carla Souza'] as $name) {
            Assignee::firstOrCreate(['name' => $name]);
        }
    }
}
