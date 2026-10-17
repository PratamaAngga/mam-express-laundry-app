<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'jenis_layanan';
    protected $primaryKey = 'id_layanan';
    public $timestamps = false;

    protected $fillable = ['nama_layanan', 'harga_per_kg', 'estimasi_hari'];
}