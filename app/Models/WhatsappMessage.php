<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Buku catatan pesan WhatsApp keluar (tabel notifikasi_wa). */
class WhatsappMessage extends Model
{
    public const TIPE_INVOICE_PELANGGAN = 'invoice_pelanggan';
    public const TIPE_INVOICE_SUPPLIER = 'invoice_supplier';
    public const TIPE_STATUS_PELANGGAN = 'status_pelanggan';
    public const TIPE_PERINGATAN_STOK = 'peringatan_stok';

    public const PENDING = 'pending';
    public const TERKIRIM = 'terkirim';
    public const GAGAL = 'gagal';
    public const SIMULASI = 'simulasi'; // dicatat driver log, tidak benar-benar dikirim

    protected $table = 'notifikasi_wa';
    protected $primaryKey = 'id_notifikasi';
    public $timestamps = false;

    protected $fillable = [
        'tipe', 'id_pesanan', 'id_restock', 'no_tujuan', 'isi_pesan',
        'status_kirim', 'waktu_kirim', 'percobaan', 'alasan_gagal',
    ];

    protected function casts(): array
    {
        return [
            'waktu_kirim' => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return $this->status_kirim === self::PENDING;
    }
}
