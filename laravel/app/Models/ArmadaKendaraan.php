<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArmadaKendaraan extends Model
{
    protected $table = 'armada_kendaraan';

    protected $fillable = [
        'id_pusat',
        'plat_nomor',
        'max_berat_kg',
        'max_vol_m3',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'max_berat_kg' => 'decimal:2',
            'max_vol_m3' => 'decimal:4',
            'status' => 'string',
        ];
    }

    public function pusatDistribusi(): BelongsTo
    {
        return $this->belongsTo(PusatDistribusi::class, 'id_pusat');
    }

    /**
     * Scope: hanya kendaraan yang tersedia.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'Available');
    }
}
