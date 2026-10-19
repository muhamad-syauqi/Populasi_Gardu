<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PopulasiGarduSeeder extends Seeder
{
    public function run(): void
    {
        $padaleunyi = DB::table('ruas_tol')->insertGetId([
            'nama_ruas' => 'Padaleunyi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cipularang = DB::table('ruas_tol')->insertGetId([
            'nama_ruas' => 'Cipularang',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $gerbangPadaleunyi = [
            'Padalarang',
            'Cileunyi',
            'Pasteur',
            'Baros 1',
            'Baros 2',
            'Pasir Koja',
            'Kopo',
            'Toha',
            'Buah Batu',
            'Gede Bage',
        ];

        $gerbangCipularang = [
            'Cikamuning',
            'Darangdan',
            'Jatiluhur',
            'Sadang',
            'Kalitama',
        ];

        foreach ($gerbangPadaleunyi as $nama) {
            DB::table('gerbang')->insert([
                'ruas_id' => $padaleunyi,
                'nama_gerbang' => $nama,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($gerbangCipularang as $nama) {
            DB::table('gerbang')->insert([
                'ruas_id' => $cipularang,
                'nama_gerbang' => $nama,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ([
            'GTO Single',
            'GTO Mini',
            'GSO M',
            'GSO S',
            'GTM',
        ] as $nama) {
            DB::table('tipe_gardu')->insert([
                'nama_tipe' => $nama,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
