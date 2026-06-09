<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kabupaten extends Model
{
    use SoftDeletes;

    protected $table = 'kabupaten';

    protected $fillable = ['nama'];

    public function desa(): HasMany
    {
        return $this->hasMany(Desa::class, 'id_kabupaten');
    }

    public function pusatDistribusi(): HasMany
    {
        return $this->hasMany(PusatDistribusi::class, 'id_kabupaten');
    }
}
