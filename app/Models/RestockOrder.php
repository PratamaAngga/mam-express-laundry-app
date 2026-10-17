<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestockOrder extends Model
{
    public const FLOW = ['diajukan', 'dikonfirmasi', 'dikirim', 'diterima'];

    public const LABELS = [
        'diajukan'     => 'Submitted',
        'dikonfirmasi' => 'Confirmed',
        'dikirim'      => 'Sent',
        'diterima'     => 'Accepted',
    ];

    protected $table = 'pemesanan_restock';
    protected $primaryKey = 'id_restock';
    public $timestamps = false;

    protected $fillable = [
        'kode_restock', 'id_supplier', 'id_user_owner', 'status', 'catatan',
        'tanggal_pengajuan', 'tanggal_dikonfirmasi', 'tanggal_dikirim', 'tanggal_diterima',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengajuan'    => 'datetime',
            'tanggal_dikonfirmasi' => 'datetime',
            'tanggal_dikirim'      => 'datetime',
            'tanggal_diterima'     => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_owner', 'id_user');
    }

    public function details(): HasMany
    {
        return $this->hasMany(RestockDetail::class, 'id_restock', 'id_restock');
    }

    public function getTotalAttribute(): float
    {
        return $this->details->sum(fn ($detail) => $detail->subtotal);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::LABELS[$this->status] ?? $this->status;
    }

    /**
     * The 4-step "Booking Process" timeline shown in the detail panel.
     */
    public function timeline(): array
    {
        $current = array_search($this->status, self::FLOW);

        $steps = [
            ['Submitted', 'It has been sent to the supplier', $this->tanggal_pengajuan],
            ['Confirmed', 'Quantity and price confirmed',     $this->tanggal_dikonfirmasi],
            ['Sent',      'Items in the shipment',            $this->tanggal_dikirim],
            ['Accepted',  'Stock added',                      $this->tanggal_diterima],
        ];

        return collect($steps)->map(fn ($step, $i) => [
            'label'       => $step[0],
            'description' => $step[1],
            'time'        => $i <= $current ? $step[2] : null,
            'done'        => $i <= $current,
        ])->all();
    }

    /** 400000 -> "Rp400.000" */
    public static function rupiah(float|int|string $value): string
    {
        return 'Rp' . number_format((float) $value, 0, ',', '.');
    }
}