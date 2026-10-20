<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerangkatGardu extends Model
{
    public const COUNT_FIELDS = [
        'reader_atas', 'reader_bawah',
        'cctv_lajur', 'cctv_gandar',
        'lpr_custom_atas', 'lpr_custom_bawah',
        'pyramid_atas', 'pyramid_bawah',
        'hs_atas', 'hs_bawah',
    ];

    public const ALB_FIELDS = [
        'alb_tollpro' => 'Tollpro',
        'alb_highspeed' => 'Highspeed',
        'alb_grs' => 'GRS',
        'alb_automation' => 'Automation',
    ];

    public const PRINTER_OPTIONS = [
        'HP Lj P1101',
        'HP Lj M12W',
        'HP Lj 107w',
        'HP Lj 108w',
    ];

    protected $table = 'perangkat_gardu';

    protected $fillable = [
        'gardu_id',
        ...self::COUNT_FIELDS,
        'alb_tollpro', 'alb_highspeed', 'alb_grs', 'alb_automation',
        'printer_css', 'catatan',
    ];

    public function gardu(): BelongsTo
    {
        return $this->belongsTo(Gardu::class, 'gardu_id');
    }
}