<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Desa extends Model
{
    protected $table = 'desa';

    protected $fillable = [
        'id_kabupaten',
        'nama',
        'lat',
        'long_decimal',
        'populasi',
        'korban_selamat',
        'jumlah_orang_sakit',
        'persentase_infrastruktur_rusak',
        'status_isolasi',
        'status_aman',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:8',
            'long_decimal' => 'decimal:8',
            'populasi' => 'integer',
            'korban_selamat' => 'integer',
            'jumlah_orang_sakit' => 'integer',
            'persentase_infrastruktur_rusak' => 'decimal:2',
            'status_isolasi' => 'boolean',
            'status_aman' => 'boolean',
        ];
    }

    public function kabupaten(): BelongsTo
    {
        return $this->belongsTo(Kabupaten::class, 'id_kabupaten');
    }

    public function demands(): HasMany
    {
        return $this->hasMany(DemandKebutuhan::class, 'id_desa');
    }

    /**
     * Scope: desa yang tidak aman (terdampak bencana).
     */
    public function scopeTerdampak($query)
    {
        return $query->where('status_aman', false);
    }
}
