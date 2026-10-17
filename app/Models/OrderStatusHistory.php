<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    protected $table = 'riwayat_status_pesanan';
    protected $primaryKey = 'id_riwayat';
    public $timestamps = false;

    protected $fillable = ['id_pesanan', 'id_user', 'status_lama', 'status_baru', 'waktu_perubahan'];

    protected function casts(): array
    {
        return [
            'waktu_perubahan' => 'datetime',
        ];
    }
}
