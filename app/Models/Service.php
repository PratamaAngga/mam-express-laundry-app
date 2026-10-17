<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'jenis_layanan';
    protected $primaryKey = 'id_layanan';
    public $timestamps = false;

    protected $fillable = ['nama_layanan', 'harga_per_kg', 'estimasi_hari', 'status_aktif'];

    protected function casts(): array
    {
        return [
            'harga_per_kg'  => 'float',
            'estimasi_hari' => 'integer',
            'status_aktif'  => 'boolean',
        ];
    }

    /** Only active services should be offered to cashiers. */
    public function scopeActive($query)
    {
        return $query->where('status_aktif', true);
    }

    /** 3 -> "3 days", 1 -> "1 day" */
    public function getDaysLabelAttribute(): string
    {
        return $this->estimasi_hari . ($this->estimasi_hari === 1 ? ' day' : ' days');
    }

    /** 8000 -> "Rp8.000" */
    public static function rupiah(float|int|string $value): string
    {
        return 'Rp' . number_format((float) $value, 0, ',', '.');
    }
}
