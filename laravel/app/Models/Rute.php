<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rute extends Model
{
    protected $table = 'rute';

    protected $fillable = [
        'id_titik_asal',
        'id_titik_tujuan',
        'jarak_km',
        'status_akses_terbuka',
    ];

    protected function casts(): array
    {
        return [
            'jarak_km' => 'float',
            'status_akses_terbuka' => 'boolean',
        ];
    }

    /**
     * Resolve titik asal — could be Desa or PusatDistribusi.
     * Uses polymorphic-like resolution across both tables.
     */
    public function resolveTitikAsal()
    {
        return Desa::find($this->id_titik_asal)
            ?? PusatDistribusi::find($this->id_titik_asal);
    }

    /**
     * Resolve titik tujuan — could be Desa or PusatDistribusi.
     */
    public function resolveTitikTujuan()
    {
        return Desa::find($this->id_titik_tujuan)
            ?? PusatDistribusi::find($this->id_titik_tujuan);
    }

    /**
     * Scope: hanya rute yang terbuka/aktif.
     */
    public function scopeTerbuka($query)
    {
        return $query->where('status_akses_terbuka', true);
    }

    /**
     * Scope: hanya rute yang terblokir.
     */
    public function scopeTerblokir($query)
    {
        return $query->where('status_akses_terbuka', false);
    }
}

