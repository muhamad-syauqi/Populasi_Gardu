<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerangkatGardu extends Model
{
    public const COUNT_FIELDS = [
        'reader_atas', 'reader_bawah',
        'cctv_vivotek_lajur', 'cctv_vivotek_gandar',
        'cctv_milesight_lajur', 'cctv_milesight_gandar',
        'mr_entrance', 'mr_exit',
        'lpr_custom_atas', 'lpr_custom_bawah',
        'pyramid_atas', 'pyramid_bawah',
        'hs_atas', 'hs_bawah',
    ];

    public const ALB_FIELDS = [
        'alb_tollpro' => 'Tollpro',
        'alb_highspeed' => 'Highspeed',
        'alb_grs' => 'GRS',
        'alb_automation' => 'Automation',
        'alb_akses_pro' => 'Akses Pro'
    ];

    public const PRINTER_OPTIONS = [
        'HP Lj P1101',
        'HP Lj M12W',
        'HP Lj 107w',
        'HP Lj 108w',
        'Epson L3210',
    ];

    public const PRINTER_CSS_OPTIONS = [
        'HP Lj M12W',
        'HP Lj 107W',
        'HP Lj 108W',
        'Epson L320',
    ];

    protected $table = 'perangkat_gardu';

    protected $fillable = [
        'gardu_id',
        ...self::COUNT_FIELDS,
        'alb_tollpro', 'alb_highspeed', 'alb_grs', 'alb_automation', 'alb_akses_pro',
        'printer_css', 'printer_css_2', 'catatan',
    ];

    public function gardu(): BelongsTo
    {
        return $this->belongsTo(Gardu::class, 'gardu_id');
    }
}