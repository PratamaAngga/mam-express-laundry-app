<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $table = 'pelanggan';
    protected $primaryKey = 'id_pelanggan';

    protected $fillable = ['nama', 'no_telepon'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'id_pelanggan', 'id_pelanggan');
    }
}