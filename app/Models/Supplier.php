<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $table = 'supplier';
    protected $primaryKey = 'id_supplier';
    public $timestamps = false;

    protected $fillable = ['id_user', 'nama_supplier', 'no_whatsapp', 'alamat'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function rawMaterials(): HasMany
    {
        return $this->hasMany(RawMaterial::class, 'id_supplier', 'id_supplier');
    }

    public function restockOrders(): HasMany
    {
        return $this->hasMany(RestockOrder::class, 'id_supplier', 'id_supplier');
    }

    /** The supplier is active when its login account is active. */
    public function getIsActiveAttribute(): bool
    {
        return (bool) $this->user?->status_aktif;
    }

    /** "081188005500" -> "0811 8800 5500" */
    public function getWhatsappLabelAttribute(): string
    {
        $digits = preg_replace('/\D/', '', $this->no_whatsapp);

        return trim(chunk_split($digits, 4, ' '));
    }

    /** "0812..." -> "https://wa.me/62812..." */
    public function getWhatsappUrlAttribute(): string
    {
        $digits = preg_replace('/\D/', '', $this->no_whatsapp);

        return 'https://wa.me/' . preg_replace('/^0/', '62', $digits);
    }
}