<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    /** Available report types (more can be added later, e.g. stock and restock reports for FR-19). */
    public const TYPES = [
        'orders' => 'Order transactions',
    ];

    public function index(Request $request)
    {
        return view('owner.reports.index', $this->buildReport($request));
    }

    /** Print-friendly page; the owner chooses "Save as PDF" in the print dialog. */
    public function printView(Request $request)
    {
        return view('owner.reports.print', $this->buildReport($request));
    }

    /** Excel-compatible CSV download. */
    public function export(Request $request)
    {
        $report = $this->buildReport($request);

        $filename = 'order-report-' . $report['start']->format('Y-m-d') . '_to_' . $report['end']->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");   // UTF-8 BOM so Excel reads the characters correctly
            fwrite($out, "sep=;\r\n");      // tells Excel the delimiter, whatever the regional settings

            $put = fn (array $row) => fputcsv($out, $row, ';', '"', '\\');

            $put(['Date', 'Order code', 'Customer', 'Service', 'Weight (kg)', 'Revenue (Rp)', 'Payment']);

            foreach ($report['orders'] as $o) {
                $put([
                    $o->waktu_pesanan->format('Y-m-d'),
                    $o->kode_pesanan,
                    $o->customer->nama,
                    $o->service->nama_layanan,
                    Order::kg($o->berat_kg),
                    (int) $o->total_harga,
                    $o->is_paid ? 'Fully paid' : 'Unpaid',
                ]);
            }

            $put([]);
            $put(['Total', $report['summary']['orders'] . ' orders', '', '', Order::kg($report['summary']['weight']), (int) $report['summary']['revenue'], '']);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Shared by the page, the print view, and the export so the numbers always match.
     */
    private function buildReport(Request $request): array
    {
        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
            'type'       => ['nullable', Rule::in(array_keys(self::TYPES))],
        ]);

        // Default period: the last 7 days, ending today
        $end = isset($data['end_date']) ? Carbon::parse($data['end_date']) : today();
        $start = isset($data['start_date']) ? Carbon::parse($data['start_date']) : $end->copy()->subDays(6);

        if ($start->gt($end)) {
            $end = $start->copy();
        }

        $type = $data['type'] ?? 'orders';

        $orders = Order::with(['customer', 'service'])
            ->whereBetween('waktu_pesanan', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->orderByDesc('waktu_pesanan')
            ->get();

        // Revenue only counts fully paid orders
        $paid = $orders->filter(fn ($o) => $o->is_paid);

        $summary = [
            'orders'  => $orders->count(),
            'revenue' => $paid->sum('total_harga'),
            'weight'  => $orders->sum('berat_kg'),
            'unpaid'  => $orders->count() - $paid->count(),
        ];
        $summary['average_weight'] = $summary['orders'] > 0 ? $summary['weight'] / $summary['orders'] : 0;

        // Revenue breakdown: add-ons per type, the rest is the main service
        $addons = $paid->isEmpty()
            ? collect()
            : DB::table('pesanan_tambahan as pt')
                ->join('layanan_tambahan as lt', 'lt.id_tambahan', '=', 'pt.id_tambahan')
                ->whereIn('pt.id_pesanan', $paid->pluck('id_pesanan')->all())
                ->selectRaw('lt.nama_tambahan as name, SUM(pt.harga_saat_ini) as total')
                ->groupBy('lt.nama_tambahan')
                ->orderBy('lt.nama_tambahan')
                ->get();

        $breakdown = [
            'main'   => $summary['revenue'] - $addons->sum('total'),
            'addons' => $addons,
        ];

        return [
            'start'       => $start,
            'end'         => $end,
            'type'        => $type,
            'types'       => self::TYPES,
            'typeLabel'   => self::TYPES[$type],
            'orders'      => $orders,
            'summary'     => $summary,
            'breakdown'   => $breakdown,
            'periodLabel' => $this->periodLabel($start, $end),
        ];
    }

    /** "October 3–9, 2026" / "October 9, 2026" / "Oct 28, 2026 – Nov 3, 2026" */
    private function periodLabel(Carbon $start, Carbon $end): string
    {
        if ($start->isSameDay($end)) {
            return $start->format('F j, Y');
        }

        if ($start->isSameMonth($end)) {
            return $start->format('F j') . '–' . $end->format('j, Y');
        }

        return $start->format('M j, Y') . ' – ' . $end->format('M j, Y');
    }
}