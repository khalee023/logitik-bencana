<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ManifestPengiriman extends Model
{
    protected $table = 'manifest_pengiriman';

    protected $fillable = [
        'kode_manifest',
        'id_pusat',
        'id_armada',
        'status',
        'route_json',
        'waktu_berangkat',
        'waktu_tiba',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'route_json' => 'array',
            'waktu_berangkat' => 'datetime',
            'waktu_tiba' => 'datetime',
        ];
    }

    /**
     * Auto-generate UUID saat pembuatan record baru.
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->attributes['kode_manifest'])) {
                $model->kode_manifest = Str::uuid()->toString();
            }
        });
    }

    /**
     * Accessor: konversi BINARY(16) → string UUID.
     */
    public function getKodeManifestAttribute($value)
    {
        if ($value === null) {
            return null;
        }
        if (strlen($value) === 16) {
            return \Ramsey\Uuid\Uuid::fromBytes($value)->toString();
        }
        return $value;
    }

    /**
     * Mutator: konversi string UUID → BINARY(16).
     */
    public function setKodeManifestAttribute($value)
    {
        if (is_string($value) && strlen($value) === 36) {
            $this->attributes['kode_manifest'] = \Ramsey\Uuid\Uuid::fromString($value)->getBytes();
        } else {
            $this->attributes['kode_manifest'] = $value;
        }
    }

    public function pusatDistribusi(): BelongsTo
    {
        return $this->belongsTo(PusatDistribusi::class, 'id_pusat');
    }

    public function armada(): BelongsTo
    {
        return $this->belongsTo(ArmadaKendaraan::class, 'id_armada');
    }
}
