<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** Database status => label shown on the dashboard */
    public const STATUS_LABELS = [
        'antre_cuci'           => 'Accepted',
        'diproses'             => 'Processing',
        'dikemas_siap_diambil' => 'Ready for pickup',
        'selesai'              => 'Done',
    ];

    // 4 status resmi (kesepakatan tim, sesuai proposal). Urutan = alur kerja.
    // Label tampilan ada di STATUS_LABELS di atas, jadi tidak ada daftar label kedua.
    public const STATUS_QUEUED    = 'antre_cuci';
    public const STATUS_PROCESSED = 'diproses';
    public const STATUS_PACKED    = 'dikemas_siap_diambil';
    public const STATUS_DONE      = 'selesai';

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

    // Layanan tambahan (mis. pewangi). Harga saat pesanan dibuat ada di pivot: harga_saat_ini.
    public function additionalServices(): BelongsToMany
    {
        return $this->belongsToMany(
            AdditionalService::class,
            'pesanan_tambahan',
            'id_pesanan',
            'id_tambahan',
            'id_pesanan',
            'id_tambahan'
        )->withPivot('harga_saat_ini');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'id_pesanan', 'id_pesanan');
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status_pembayaran === 'lunas';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
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