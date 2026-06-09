<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokPusat extends Model
{
    protected $table = 'stok_pusat';

    protected $fillable = [
        'id_pusat',
        'id_barang',
        'total_kuantitas',
    ];

    protected function casts(): array
    {
        return [
            'total_kuantitas' => 'integer',
        ];
    }

    public function pusatDistribusi(): BelongsTo
    {
        return $this->belongsTo(PusatDistribusi::class, 'id_pusat');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(MasterBantuan::class, 'id_barang');
    }
}
