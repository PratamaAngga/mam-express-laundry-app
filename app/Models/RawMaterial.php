<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterial extends Model
{
    protected $table = 'bahan_baku';
    protected $primaryKey = 'id_bahan';

    // The table only has updated_at (no created_at)
    const CREATED_AT = null;

    protected $fillable = ['nama_bahan', 'satuan', 'stok_saat_ini', 'stok_minimum', 'id_supplier', 'pemakaian_per_kg', 'id_tambahan_pemicu'];

    protected function casts(): array
    {
        return [
            'stok_saat_ini' => 'float',
            'stok_minimum'  => 'float',
            'pemakaian_per_kg' => 'float',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }

    public function stockLogs(): HasMany
    {
        return $this->hasMany(StockLog::class, 'id_bahan', 'id_bahan');
    }

    /** Stock at or below the minimum level (FR-12). */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stok_saat_ini', '<=', 'stok_minimum');
    }

    public function getIsLowAttribute(): bool
    {
        return $this->stok_saat_ini <= $this->stok_minimum;
    }

    /** 2 -> "2", 0.5 -> "0,5", 1000 -> "1.000" */
    public static function formatNumber(float|int|string $value): string
    {
        $text = number_format((float) $value, 3, ',', '.');

        return rtrim(rtrim($text, '0'), ',');
    }

    /** "Oct. 9, 2026, 10:30 a.m." as in the Figma design */
    public function getUpdatedLabelAttribute(): string
    {
        if (! $this->updated_at) {
            return '-';
        }

        $t = $this->updated_at;

        return $t->format('M. j, Y, g:i ') . ($t->format('a') === 'am' ? 'a.m.' : 'p.m.');
    }
}