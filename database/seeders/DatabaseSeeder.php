<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // GANTI password ini setelah login pertama.
        User::firstOrCreate(
            ['username' => 'admin'],
            ['name' => 'admin', 'email' => 'admin@intracs.local', 'password' => 'Admin#12345']
        );

        if (DB::table('ruas_tol')->doesntExist()) {
            $this->call(PopulasiGarduSeeder::class);
        }
    }
}