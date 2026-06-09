<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DemandKebutuhan extends Model
{
    protected $table = 'demand_kebutuhan';

    protected $fillable = [
        'kode_batch',
        'id_desa',
        'id_barang',
        'kuantitas',
        'urgency_score',
        'target_deadline_jam',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'urgency_score' => 'decimal:4',
            'target_deadline_jam' => 'decimal:2',
            'kuantitas' => 'integer',
        ];
    }

    /**
     * Auto-generate UUID saat pembuatan record baru.
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->attributes['kode_batch'])) {
                $model->kode_batch = Str::uuid()->toString();
            }
        });
    }

    /**
     * Accessor: konversi BINARY(16) → string UUID.
     */
    public function getKodeBatchAttribute($value)
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
    public function setKodeBatchAttribute($value)
    {
        if (is_string($value) && strlen($value) === 36) {
            $this->attributes['kode_batch'] = \Ramsey\Uuid\Uuid::fromString($value)->getBytes();
        } else {
            $this->attributes['kode_batch'] = $value;
        }
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class, 'id_desa');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(MasterBantuan::class, 'id_barang');
    }

    /**
     * Scope: demand dengan status tertentu.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
