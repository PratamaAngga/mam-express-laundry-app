<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RawMaterial;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public const RANGES = [
        'today'  => 'Today',
        '7'      => '7 Days',
        '30'     => '30 Days',
        'custom' => 'Custom',
    ];

    /**
     * Dashboard: operational summary (FR-17) and service usage trend (FR-18).
     */
    public function index(Request $request)
    {
        return view('owner.dashboard', $this->buildDashboard($request));
    }

    /** Downloads the dashboard summary as an Excel-compatible CSV. */
    public function export(Request $request)
    {
        $d = $this->buildDashboard($request);

        $filename = 'dashboard-summary-' . $d['start']->format('Y-m-d') . '_to_' . $d['end']->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($d) {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");
            fwrite($out, "sep=;\r\n");

            $put = fn (array $row) => fputcsv($out, $row, ';', '"', '\\');

            $put(['Mam Express - Dashboard summary']);
            $put(['Period', $d['periodLabel']]);
            $put([]);
            $put(['Total orders', $d['summary']['orders']]);
            $put(['Revenue (Rp)', (int) $d['summary']['revenue']]);
            $put(['Total weight (kg)', Order::kg($d['summary']['weight'])]);
            $put(['Average weight (kg)', Order::kg(round($d['summary']['average_weight'], 2))]);
            $put(['Active orders', $d['summary']['active']]);
            $put([]);
            $put(['Orders by status', 'Count']);
            foreach ($d['statusCounts'] as $s) {
                $put([$s['label'], $s['count']]);
            }
            $put([]);
            $put(['Use of the service', 'Orders', 'Percent']);
            foreach ($d['services'] as $s) {
                $put([$s['name'], $s['count'], $s['percent'] . '%']);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Shared by the page and the export so the numbers always match. */
    private function buildDashboard(Request $request): array
    {
        $data = $request->validate([
            'range'      => ['nullable', Rule::in(array_map('strval', array_keys(self::RANGES)))],
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $range = (string) ($data['range'] ?? '7');
        $today = today();

        [$start, $end] = match ($range) {
            'today'  => [$today->copy(), $today->copy()],
            '30'     => [$today->copy()->subDays(29), $today->copy()],
            'custom' => [
                isset($data['start_date']) ? Carbon::parse($data['start_date']) : $today->copy()->subDays(6),
                isset($data['end_date']) ? Carbon::parse($data['end_date']) : $today->copy(),
            ],
            default  => [$today->copy()->subDays(6), $today->copy()],
        };

        if ($start->gt($end)) {
            $end = $start->copy();
        }

        // Keep the chart readable: at most one year
        if (abs($start->diffInDays($end)) > 365) {
            $start = $end->copy()->subDays(365);
        }

        $orders = Order::with(['customer', 'service'])
            ->whereBetween('waktu_pesanan', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->orderByDesc('waktu_pesanan')
            ->get();

        // Same rule as the Report page: revenue only counts fully paid orders
        $paid = $orders->filter(fn ($o) => $o->is_paid);

        $summary = [
            'orders'      => $orders->count(),
            'revenue'     => $paid->sum('total_harga'),
            'unpaid'      => $orders->count() - $paid->count(),
            'weight'      => $orders->sum('berat_kg'),
            'active'      => $orders->where('status', '!=', 'selesai')->count(),
            'ready'       => $orders->where('status', 'dikemas_siap_diambil')->count(),
            'today_count' => Order::whereDate('waktu_pesanan', today())->count(),
        ];
        $summary['average_weight'] = $summary['orders'] > 0 ? $summary['weight'] / $summary['orders'] : 0;

        // Daily trend (one point per day, empty days are 0)
        $perDay = $orders->countBy(fn ($o) => $o->waktu_pesanan->format('Y-m-d'));
        $labels = [];
        $counts = [];

        foreach (CarbonPeriod::create($start, $end) as $day) {
            $labels[] = $day->format('j M');
            $counts[] = $perDay[$day->format('Y-m-d')] ?? 0;
        }

        // Use of the service
        $total = max($summary['orders'], 1);
        $services = $orders->groupBy('id_layanan')
            ->map(fn ($group) => [
                'name'  => $group->first()->service->nama_layanan,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->map(fn ($s) => $s + ['percent' => (int) round($s['count'] / $total * 100)])
            ->all();

        // Orders by status
        $statusCounts = collect(Order::STATUS_LABELS)
            ->map(fn ($label, $key) => [
                'key'   => $key,
                'label' => $label,
                'count' => $orders->where('status', $key)->count(),
            ])
            ->values()
            ->all();

        // Materials below the minimum level, the most critical first
        $lowStock = RawMaterial::with('supplier')->lowStock()->get()
            ->sortBy(fn ($m) => $m->stok_saat_ini / max($m->stok_minimum, 1))
            ->values();

        $hour = now()->hour;

        return [
            'range'        => $range,
            'ranges'       => self::RANGES,
            'start'        => $start,
            'end'          => $end,
            'periodLabel'  => $this->periodLabel($start, $end),
            'summary'      => $summary,
            'labels'       => $labels,
            'counts'       => $counts,
            'services'     => $services,
            'statusCounts' => $statusCounts,
            'lowStock'     => $lowStock,
            'latest'       => $orders->take(5),
            'greeting'     => $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening'),
            'firstName'    => Str::before(auth()->user()->nama, ' '),
        ];
    }

    /** "3–9 October 2026" / "9 October 2026" / "28 Oct 2026 – 3 Nov 2026" */
    private function periodLabel(Carbon $start, Carbon $end): string
    {
        if ($start->isSameDay($end)) {
            return $start->format('j F Y');
        }

        if ($start->isSameMonth($end)) {
            return $start->format('j') . '–' . $end->format('j F Y');
        }

        return $start->format('j M Y') . ' – ' . $end->format('j M Y');
    }
}
