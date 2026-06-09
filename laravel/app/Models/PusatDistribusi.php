<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PusatDistribusi extends Model
{
    protected $table = 'pusat_distribusi';

    protected $fillable = [
        'id_kabupaten',
        'nama',
        'lat',
        'long_decimal',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:8',
            'long_decimal' => 'decimal:8',
            'status_aktif' => 'boolean',
        ];
    }

    public function kabupaten(): BelongsTo
    {
        return $this->belongsTo(Kabupaten::class, 'id_kabupaten');
    }

    public function armada(): HasMany
    {
        return $this->hasMany(ArmadaKendaraan::class, 'id_pusat');
    }

    public function manifests(): HasMany
    {
        return $this->hasMany(ManifestPengiriman::class, 'id_pusat');
    }

    /**
     * Relasi many-to-many ke MasterBantuan melalui pivot stok_pusat.
     */
    public function stokBarang(): BelongsToMany
    {
        return $this->belongsToMany(MasterBantuan::class, 'stok_pusat', 'id_pusat', 'id_barang')
                    ->withPivot('total_kuantitas')
                    ->withTimestamps();
    }

    /**
     * Scope: hanya pusat distribusi aktif.
     */
    public function scopeAktif($query)
    {
        return $query->where('status_aktif', true);
    }
}
