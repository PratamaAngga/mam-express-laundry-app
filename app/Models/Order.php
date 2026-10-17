<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $table = 'pesanan';
    protected $primaryKey = 'id_pesanan';
    public $timestamps = false;

    protected $fillable = [
        'kode_pesanan', 'id_pelanggan', 'id_user_kasir', 'id_layanan', 'berat_kg',
        'total_harga', 'status_pembayaran', 'status', 'token_tracking', 'waktu_pesanan', 'waktu_selesai',
    ];

    protected function casts(): array
    {
        return [
            'berat_kg'      => 'float',
            'total_harga'   => 'float',
            'waktu_pesanan' => 'datetime',
            'waktu_selesai' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'id_layanan', 'id_layanan');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_kasir', 'id_user');
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status_pembayaran === 'lunas';
    }

    /** 41000 -> "Rp41.000" */
    public static function rupiah(float|int|string $value): string
    {
        return 'Rp' . number_format((float) $value, 0, ',', '.');
    }

    /** 4.5 -> "4,5", 3 -> "3" */
    public static function kg(float|int|string $value): string
    {
        $text = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');

        return str_replace('.', ',', $text);
    }
}