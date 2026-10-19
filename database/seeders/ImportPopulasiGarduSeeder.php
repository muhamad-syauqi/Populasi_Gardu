<?php

namespace Database\Seeders;

use App\Models\Gardu;
use App\Models\Gerbang;
use App\Models\RuasTol;
use App\Models\TipeGardu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImportPopulasiGarduSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(
            file_get_contents(database_path('data/populasi_gardu.json')),
            true, 512, JSON_THROW_ON_ERROR
        );

        DB::transaction(function () use ($data) {
            $tipe = [];
            foreach (['GTO Single', 'GTO Mini', 'GSO M', 'GSO S', 'GTM'] as $nama) {
                $tipe[$nama] = TipeGardu::firstOrCreate(['nama_tipe' => $nama])->id;
            }

            foreach ($data as $g) {
                $ruas = RuasTol::firstOrCreate(['nama_ruas' => $g['ruas']]);
                $gerbang = Gerbang::firstOrCreate([
                    'ruas_id' => $ruas->id,
                    'nama_gerbang' => $g['gerbang'],
                ]);

                foreach ($g['gardu'] as $row) {
                    $gardu = Gardu::updateOrCreate(
                        ['gerbang_id' => $gerbang->id, 'nomor_gardu' => $row['nomor']],
                        ['tipe_gardu_id' => $tipe[$row['tipe']] ?? null, 'status' => 'Aktif']
                    );

                    $gardu->perangkat()->updateOrCreate([], [
                        'reader_atas' => $row['reader_atas'],
                        'reader_bawah' => $row['reader_bawah'],
                        'cctv_lajur' => $row['cctv_lajur'],
                        'cctv_gandar' => $row['cctv_gandar'],
                    ]);
                }
            }
        });
    }
}