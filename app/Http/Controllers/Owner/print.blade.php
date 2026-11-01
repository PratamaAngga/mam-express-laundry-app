@php use App\Models\Order; @endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $typeLabel }} — {{ $periodLabel }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #12332d;
            margin: 32px;
            font-size: 13px;
        }

        h1 {
            margin: 0 0 4px;
            font-size: 22px;
        }

        .muted {
            color: #6b7280;
        }

        .summary {
            display: flex;
            gap: 16px;
            margin: 20px 0;
        }

        .card {
            flex: 1;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 16px;
        }

        .card strong {
            display: block;
            font-size: 20px;
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th {
            background: #eef3f2;
            text-align: left;
            padding: 10px;
            font-weight: 600;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .total {
            margin-top: 16px;
            background: #e6f4f1;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            font-weight: 700;
            border-radius: 6px;
        }

        .breakdown {
            margin-top: 24px;
            max-width: 360px;
        }

        .breakdown div {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
        }

        .no-print {
            margin-bottom: 20px;
        }

        .no-print button {
            background: #0f766e;
            color: #fff;
            border: 0;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button onclick="window.print()">Print / Save as PDF</button>
        <span class="muted">In the print dialog, choose "Save as PDF" as the destination.</span>
    </div>

    <h1>Mam Express — {{ $typeLabel }}</h1>
    <p class="muted">Candi Panggung, Malang • {{ $periodLabel }} • {{ $summary['orders'] }} transactions</p>

    <div class="summary">
        <div class="card">Total orders <strong>{{ $summary['orders'] }}</strong></div>
        <div class="card">Revenue <strong>{{ Order::rupiah($summary['revenue']) }}</strong></div>
        <div class="card">Total weight <strong>{{ Order::kg($summary['weight']) }} kg</strong></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Order code</th>
                <th>Customer</th>
                <th>Service</th>
                <th>Weight</th>
                <th>Revenue</th>
                <th>Paid</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $o)
                <tr>
                    <td>{{ $o->waktu_pesanan->format('M. j, Y') }}</td>
                    <td>{{ $o->kode_pesanan }}</td>
                    <td>{{ $o->customer->nama }}</td>
                    <td>{{ $o->service->nama_layanan }}</td>
                    <td>{{ Order::kg($o->berat_kg) }} kg</td>
                    <td>{{ Order::rupiah($o->total_harga) }}</td>
                    <td>{{ $o->is_paid ? 'Fully paid' : 'Unpaid' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted" style="text-align:center">No transactions in this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="total">
        <span>Total • {{ $summary['orders'] }} orders • {{ Order::kg($summary['weight']) }} kg</span>
        <span>{{ Order::rupiah($summary['revenue']) }}</span>
    </div>

    <div class="breakdown">
        <h3>Revenue breakdown</h3>
        <div><span>Main Services</span><span>{{ Order::rupiah($breakdown['main']) }}</span></div>
        @foreach ($breakdown['addons'] as $addon)
            <div><span>{{ $addon->name }}</span><span>{{ Order::rupiah($addon->total) }}</span></div>
        @endforeach
    </div>

    <script>
        window.addEventListener('load', () => window.print());
    </script>
</body>

</html>
