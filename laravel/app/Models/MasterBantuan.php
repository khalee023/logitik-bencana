<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterBantuan extends Model
{
    protected $table = 'master_bantuan';

    protected $fillable = [
        'nama',
        'kategori',
        'berat_kg',
        'volume_m3',
    ];

    protected function casts(): array
    {
        return [
            'kategori' => 'string',
            'berat_kg' => 'decimal:2',
            'volume_m3' => 'decimal:4',
        ];
    }
}
