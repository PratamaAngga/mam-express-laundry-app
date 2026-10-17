<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestockOrder extends Model
{
    protected $table = 'pemesanan_restock';
    protected $primaryKey = 'id_restock';
    public $timestamps = false;

    protected $fillable = ['kode_restock', 'id_supplier', 'id_user_owner', 'status', 'tanggal_pengajuan', 'tanggal_diterima'];

    protected function casts(): array
    {
        return [
            'tanggal_pengajuan' => 'datetime',
            'tanggal_diterima'  => 'datetime',
        ];
    }
}