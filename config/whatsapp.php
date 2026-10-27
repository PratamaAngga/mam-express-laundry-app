<?php

return [

    /*
    | Jalur pengiriman WhatsApp:
    |   log    = pesan hanya dicatat (tidak ada yang dikirim) - aman untuk pengembangan dan tes
    |   fonnte = pesan dikirim sungguhan lewat Fonnte (butuh FONNTE_TOKEN)
    */
    'driver' => env('WHATSAPP_DRIVER', 'log'),

    // Jeda (detik) setelah tiap pengiriman sungguhan, supaya pola kirim wajar dan nomor lebih aman
    'gap_seconds' => (int) env('WHATSAPP_GAP_SECONDS', 3),

    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
        'url' => env('FONNTE_URL', 'https://api.fonnte.com/send'),
        'timeout' => 15,
    ],

    // Identitas toko yang dicantumkan di pesan
    'shop' => [
        'name' => env('SHOP_NAME', 'Mam Express Laundry'),
        'address' => env('SHOP_ADDRESS', 'Jl. Candi Panggung Bar. No. 61, Mojolangu, Kec. Lowokwaru, Kota Malang'),
    ],

];
