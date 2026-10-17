<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    private const FRESHENER_PRICE = 5000;

    private const STATUSES = [
        'accepted' => 'Accepted',
        'washed' => 'Washed',
        'dried' => 'Dried',
        'ready' => 'Ready for pickup',
        'done' => 'Done',
    ];

    // nama layanan, harga per kg, lama pengerjaan (hari)
    private const SERVICES = [
        'laundry_iron' => ['Laundry + Iron', 8000, 3],
        'iron' => ['Iron', 6000, 2],
    ];

    // Halaman Print Receipt
    public function index(Request $request)
    {
        $orders = $this->orders();
        $selected = collect($orders)->firstWhere('code', $request->query('order')) ?? $orders[0];

        return view('cashier.receipt.index', [
            'orders' => $orders,
            'selected' => $selected,
        ]);
    }

    // DATA DUMMY
    private function orders(): array
    {
        $rows = [
            ['BNG-261009-0428', '2026-10-09 10:42', 'Siti Rahma', '0858 5617 0561', 4.5, 'laundry_iron', true, 'accepted', true],
            ['BNG-261009-0427', '2026-10-09 09:15', 'Budi Santoso', '0813 7788 9900', 3, 'laundry_iron', false, 'washed', true],
            ['BNG-261008-0426', '2026-10-08 14:20', 'Rina Amelia', '0857 2211 4400', 5, 'laundry_iron', false, 'dried', false],
            ['BNG-261007-0425', '2026-10-07 11:30', 'Andi Pratama', '0821 6611 2200', 2, 'iron', false, 'ready', true],
            ['BNG-261006-0424', '2026-10-06 10:10', 'Dewi Lestari', '0812 8800 3311', 6, 'laundry_iron', true, 'done', true],
            ['BNG-261005-0423', '2026-10-05 15:45', 'Agus Hidayat', '0819 5522 1100', 4, 'laundry_iron', false, 'ready', false],
            ['BNG-261004-0422', '2026-10-04 12:05', 'Maya Putri', '0856 9900 2200', 3.5, 'laundry_iron', false, 'done', true],
            ['BNG-261003-0421', '2026-10-03 09:40', 'Fajar Nugroho', '0822 4488 6600', 2, 'iron', false, 'done', true],
        ];

        return array_map(function ($r) {
            [$code, $at, $name, $phone, $kg, $serviceKey, $freshener, $status, $paid] = $r;
            [$serviceName, $price, $days] = self::SERVICES[$serviceKey];

            $placed = Carbon::parse($at);
            $eta = $placed->copy()->addDays($days);

            $serviceTotal = (int) round($kg * $price);
            $freshenerTotal = $freshener ? self::FRESHENER_PRICE : 0;
            $total = $serviceTotal + $freshenerTotal;
            $paidAmount = $paid ? $total : 0;
            $kgLabel = rtrim(rtrim(number_format($kg, 1, ',', ''), '0'), ',');

            return [
                'code' => $code,
                'option_label' => $code . ' • ' . $name,
                'name' => $name,
                'phone' => $phone,
                'date_short' => $placed->format('j M Y') . ', ' . $placed->format('H.i'),
                'received' => 'Received ' . $placed->format('F j, Y') . ', ' . $this->time($placed),
                'service_name' => $serviceName,
                'calc' => $kgLabel . ' kg × ' . $this->rp($price) . '/kg',
                'service_total' => $this->rp($serviceTotal),
                'freshener' => $freshener,
                'freshener_total' => $this->rp($freshenerTotal),
                'total' => $this->rp($total),
                'paid' => $paid,
                'paid_label' => $paid ? 'paid off' : 'unpaid',
                'paid_amount' => $this->rp($paidAmount),
                'remaining' => $this->rp($total - $paidAmount),
                'eta' => $eta->format('F j, Y') . ', ' . $this->time($eta),
                'status_key' => $status,
                'status_label' => self::STATUSES[$status],
                'weight_service' => $kgLabel . ' kg • ' . $serviceName,
                'completed' => 'Completed: ' . $eta->format('M j, Y') . ', ' . $this->time($eta),
                'sent_text' => 'To ' . $phone . ' on ' . $placed->format('F j') . ' at ' . $this->time($placed) . '.',
                'qr' => $this->qrRects($code),
            ];
        }, $rows);
    }

    private function rp(int $amount): string
    {
        return 'Rp' . number_format($amount, 0, ',', '.');
    }

    // 10:42 a.m. / 3:05 p.m.
    private function time(Carbon $date): string
    {
        return $date->format('g:i') . ' ' . ($date->format('a') === 'am' ? 'a.m.' : 'p.m.');
    }

    // Ilustrasi QR (BUKAN QR asli): pola digambar dari kode order.
    private function qrRects(string $code): string
    {
        $n = 21;
        $state = crc32($code);
        $rects = '';
        $corners = [[0, 0], [$n - 7, 0], [0, $n - 7]];

        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                $dark = null;
                foreach ($corners as [$cx, $cy]) {
                    $rx = $x - $cx;
                    $ry = $y - $cy;
                    if ($rx >= -1 && $rx <= 7 && $ry >= -1 && $ry <= 7) {
                        if ($rx < 0 || $rx > 6 || $ry < 0 || $ry > 6) {
                            $dark = false;
                        } else {
                            $dark = ($rx == 0 || $rx == 6 || $ry == 0 || $ry == 6)
                                || ($rx >= 2 && $rx <= 4 && $ry >= 2 && $ry <= 4);
                        }
                    }
                }
                if ($dark === null) {
                    $state = ($state * 1103515245 + 12345) & 0x7fffffff;
                    $dark = (($state >> 16) & 1) === 1;
                }
                if ($dark) {
                    $rects .= '<rect x="' . $x . '" y="' . $y . '" width="1" height="1"/>';
                }
            }
        }

        return $rects;
    }
}