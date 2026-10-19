<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // GANTI password ini setelah login pertama.
        User::firstOrCreate(
            ['username' => 'admin'],
            ['name' => 'admin', 'email' => 'admin@intracs.local', 'password' => 'Admin#12345']
        );

        $this->call(ImportPopulasiGarduSeeder::class);
    }
}