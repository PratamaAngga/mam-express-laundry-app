<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdditionalService extends Model
{
    protected $table = 'layanan_tambahan';
    protected $primaryKey = 'id_tambahan';
    public $timestamps = false;

    protected $fillable = ['nama_tambahan', 'harga'];

    protected function casts(): array
    {
        return [
            'harga' => 'float',
        ];
    }
}
