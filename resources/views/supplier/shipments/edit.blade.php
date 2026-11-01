@extends('layouts.supplier')

@php
    use App\Models\RawMaterial;
    use App\Models\RestockOrder;

    $rupiah = fn($v) => RestockOrder::rupiah($v);
    $details = $order->details;
    $single = $details->count() === 1 ? $details->first() : null;
    $current = array_search($order->status, RestockOrder::FLOW);
    $location = 'Candi Panggung, Malang';
    $ownerName = $order->owner->nama;
    $contact = auth()->user()->nama;
    $datetime = fn($d) => $d?->format('Y-m-d\TH:i');

    $badge = [
        'dikonfirmasi' => 'bg-sky-50 text-sky-700',
        'dikirim' => 'bg-blue-50 text-blue-700',
        'diterima' => 'bg-teal-50 text-teal-700',
    ];

    $quantityText = $single
        ? RawMaterial::formatNumber($single->jumlah) . ' ' . $single->material->satuan
        : $details->count() . ' items';

    $handedText = $single
        ? $quantityText .
            ' of ' .
            strtolower($single->material->nama_bahan) .
            ' according to order ' .
            $order->kode_restock .
            '.'
        : 'All ' . $details->count() . ' items according to order ' . $order->kode_restock . '.';

    $banner = match ($order->status) {
        'dikirim' => [
            'Shipment on its way',
            'The items were marked as shipped on ' . $order->tanggal_dikirim?->format('j F Y, H.i') . '.',
        ],
        'diterima' => [
            'Shipment received',
            'The laundry team confirmed the receipt on ' . $order->tanggal_diterima?->format('j F Y, H.i') . '.',
        ],
        default => [
            'Order already confirmed',
            'Quantity of ' .
            $quantityText .
            ' and a total of ' .
            $rupiah($order->total) .
            ' were approved on ' .
            $order->tanggal_dikonfirmasi?->format('j F Y, H.i') .
            '. The items are ready for shipping.',
        ],
    };

    // Default values (old input wins after a validation error)
    $shippedDefault = $order->tanggal_dikirim ?? ($order->perkiraan_kirim ?? now()->addHour());
    $arrivalDefault = $order->estimasi_tiba ?? $shippedDefault->copy()->addHours(6)->addMinutes(30);

    $carrierValue = old('carrier', $order->ekspedisi ?? $carriers[0]);
    $carrierList = in_array($carrierValue, $carriers, true) ? $carriers : array_merge([$carrierValue], $carriers);

    $inputClass =
        'w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 disabled:bg-gray-50 disabled:text-gray-500';

    // Order history, newest first
    $history = collect([
        ['Accepted', $order->tanggal_diterima, 'Received by the laundry team.'],
        [
            'Sent',
            $order->tanggal_dikirim,
            ($order->nama_kurir ?? 'The courier') . ' is delivering the items to the branch.',
        ],
        ['Confirmed', $order->tanggal_dikonfirmasi, $contact . ' approved the quantity and delivery schedule.'],
        [
            'Submitted',
            $order->tanggal_pengajuan,
            $ownerName . ' submitted a restock request for the ' . $location . ' branch.',
        ],
    ])
        ->filter(fn($h) => $h[1] !== null)
        ->values();
@endphp

@section('title', 'Shipments')
@section('crumb', 'Shipments / ' . $order->kode_restock)
@section('heading', $readonly ? 'Shipment Details' : 'Shipping Update')
@section('subheading', $order->kode_restock . ' • ' . $supplier->nama_supplier . ($readonly ? ' • This shipment has
    already been marked as sent.' : ' • Fill in the details before marking the items as shipped.'))

@section('actions')
    <a href="{{ route('supplier.restock.show', $order) }}"
        class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg px-5 py-3 text-sm font-semibold">
        <i class="ti ti-arrow-left"></i> Back to Detail
    </a>
@endsection

@section('content')
    {{-- Flow steps --}}
    <div class="bg-white border border-gray-200 rounded-2xl px-6 py-5 mb-6 grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach (RestockOrder::LABELS as $key => $label)
            @php $index = $loop->index; @endphp
            <div class="flex items-center gap-3">
                <span
                    class="w-10 h-10 rounded-full flex items-center justify-center text-sm
                {{ $index === $current ? 'border-2 border-teal-700 text-teal-700 bg-white font-semibold' : ($index < $current ? 'bg-teal-50 text-teal-700' : 'bg-gray-100 text-gray-500') }}">
                    @if ($index < $current)
                        <i class="ti ti-check"></i>
                    @else
                        {{ $index + 1 }}
                    @endif
                </span>
                <span class="{{ $index === $current ? 'font-semibold' : 'text-gray-500' }}">{{ $label }}</span>
                @if (!$loop->last)
                    <i class="ti ti-arrow-right text-gray-400 ml-auto hidden lg:block"></i>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Status banner --}}
    <div class="mb-6 bg-teal-50 rounded-xl px-6 py-4 flex items-start gap-4">
        <i class="ti ti-circle-check text-2xl text-teal-700 mt-0.5"></i>
        <div>
            <p class="font-semibold text-teal-800">{{ $banner[0] }}</p>
            <p class="text-sm text-teal-800">{{ $banner[1] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Left: form --}}
        <div class="xl:col-span-2 space-y-6">
            <form method="POST" action="{{ route('supplier.shipments.update', $order) }}"
                class="bg-white border border-gray-200 rounded-2xl p-6">
                @csrf
                @method('PATCH')

                <h2 class="text-xl font-semibold">Shipping details</h2>
                <p class="text-sm text-gray-500 mt-1 mb-6">Complete the courier, shipping reference, and arrival schedule at
                    the laundry location.</p>

                <div class="space-y-5">
                    <div>
                        <label for="carrier" class="block text-sm mb-1.5">Carrier / courier</label>
                        <div class="relative">
                            <select name="carrier" id="carrier" @disabled($readonly)
                                class="{{ $inputClass }} appearance-none pr-10 @error('carrier') border-red-500 @enderror">
                                @foreach ($carrierList as $c)
                                    <option value="{{ $c }}" @selected($carrierValue === $c)>{{ $c }}
                                    </option>
                                @endforeach
                            </select>
                            <i class="ti ti-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        </div>
                        @error('carrier')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="courier_name" class="block text-sm mb-1.5">Courier name</label>
                            <input type="text" name="courier_name" id="courier_name" maxlength="100"
                                @disabled($readonly) value="{{ old('courier_name', $order->nama_kurir) }}"
                                placeholder="e.g. Dimas Saputra"
                                class="{{ $inputClass }} @error('courier_name') border-red-500 @enderror">
                            @error('courier_name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="courier_phone" class="block text-sm mb-1.5">Delivery contact number</label>
                            <input type="text" name="courier_phone" id="courier_phone" maxlength="20" inputmode="tel"
                                @disabled($readonly) value="{{ old('courier_phone', $order->kontak_kurir) }}"
                                placeholder="e.g. 0811 8800 5500"
                                class="{{ $inputClass }} @error('courier_phone') border-red-500 @enderror">
                            @error('courier_phone')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="tracking_number" class="block text-sm mb-1.5">Tracking number / shipping
                            reference</label>
                        <input type="text" name="tracking_number" id="tracking_number" maxlength="50"
                            @disabled($readonly) value="{{ old('tracking_number', $order->nomor_resi) }}"
                            placeholder="e.g. AB-261010-018"
                            class="{{ $inputClass }} @error('tracking_number') border-red-500 @enderror">
                        <p class="text-xs text-gray-500 mt-1.5">Internal deliveries use a delivery note reference number.
                        </p>
                        @error('tracking_number')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="shipped_at" class="block text-sm mb-1.5">Shipping date and time</label>
                            <input type="datetime-local" name="shipped_at" id="shipped_at" @disabled($readonly)
                                value="{{ old('shipped_at', $datetime($shippedDefault)) }}"
                                class="{{ $inputClass }} @error('shipped_at') border-red-500 @enderror">
                            @error('shipped_at')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="estimated_arrival" class="block text-sm mb-1.5">Estimated arrival</label>
                            <input type="datetime-local" name="estimated_arrival" id="estimated_arrival"
                                @disabled($readonly)
                                value="{{ old('estimated_arrival', $datetime($arrivalDefault)) }}"
                                class="{{ $inputClass }} @error('estimated_arrival') border-red-500 @enderror">
                            @error('estimated_arrival')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="shipping_note" class="block text-sm mb-1.5">Shipping note</label>
                        <textarea name="shipping_note" id="shipping_note" rows="3" maxlength="255" @disabled($readonly)
                            placeholder="e.g. Sealed packaging. Please check the quantity when received." class="{{ $inputClass }}">{{ old('shipping_note', $order->catatan_pengiriman) }}</textarea>
                        @error('shipping_note')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="border-t border-gray-200 mt-6 pt-5">
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="handed_over" value="1" @disabled($readonly)
                            @checked($readonly || old('handed_over'))
                            class="mt-1 w-5 h-5 rounded border-gray-300 text-teal-700 focus:ring-teal-600">
                        <span>
                            <span class="block font-semibold">Items have been handed over to the courier</span>
                            <span class="block text-sm text-gray-500">{{ $handedText }}</span>
                        </span>
                    </label>
                    @error('handed_over')
                        <p class="text-red-500 text-xs mt-1 ml-8">{{ $message }}</p>
                    @enderror

                    <div class="flex flex-wrap items-center justify-between gap-4 mt-5">
                        <p class="text-sm text-gray-500">
                            @if ($readonly)
                                The status was changed from Confirmed to Sent when this update was saved.
                            @else
                                The status changes from Confirmed to Sent after the update is saved.
                            @endif
                        </p>

                        @unless ($readonly)
                            <button type="submit"
                                class="inline-flex items-center gap-2 bg-teal-700 hover:bg-teal-800 text-white font-semibold rounded-lg px-6 py-3.5 text-sm">
                                <i class="ti ti-truck-delivery"></i> Mark as Shipped
                            </button>
                        @endunless
                    </div>
                </div>
            </form>

            <div class="bg-teal-50 rounded-xl px-6 py-4 flex items-start gap-4">
                <i class="ti ti-circle-check text-2xl text-teal-700 mt-0.5"></i>
                <div>
                    <p class="font-semibold text-teal-800">Receipt is not marked by the supplier</p>
                    <p class="text-sm text-teal-800">The Accepted status is confirmed by the laundry after the quantity,
                        item condition, and delivery note are checked.</p>
                </div>
            </div>
        </div>

        {{-- Right: summaries --}}
        <div class="space-y-6">
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h2 class="text-xl font-semibold mb-4">Order summary</h2>

                <span
                    class="inline-block px-3 py-1 rounded-full text-sm {{ $badge[$order->status] ?? 'bg-gray-100 text-gray-600' }}">{{ $order->status_label }}</span>

                <p class="font-semibold mt-5">{{ $order->kode_restock }}</p>
                <div class="flex justify-between gap-4 mt-3 text-sm">
                    <span class="text-gray-500">Supplier</span>
                    <span class="font-semibold text-right">{{ $supplier->nama_supplier }}</span>
                </div>

                <div class="border-t border-gray-200 mt-5 pt-5 space-y-4">
                    @foreach ($details as $d)
                        <div>
                            <p class="font-semibold">{{ $d->material->nama_bahan }}</p>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ RawMaterial::formatNumber($d->jumlah) }} {{ $d->material->satuan }} ×
                                {{ $rupiah($d->harga_satuan) }}
                            </p>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between mt-5">
                    <span class="text-sm text-gray-500">Order total</span>
                    <span class="text-2xl font-bold text-teal-700">{{ $rupiah($order->total) }}</span>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h2 class="text-xl font-semibold mb-4">Delivery destination</h2>

                <p class="font-semibold">Mam Express</p>
                <p class="font-semibold">{{ $location }}</p>
                <p class="text-sm text-gray-500 mt-4">Malang, East Java</p>
                <p class="text-sm text-gray-500">Recipient: {{ $ownerName }} / laundry team</p>

                <div class="border-t border-gray-200 mt-5 pt-5 flex justify-between text-sm">
                    <span class="text-gray-500">Receiving hours</span>
                    <span class="font-semibold">08.00–17.00 WIB</span>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h2 class="text-xl font-semibold mb-5">Order history</h2>

                <ol class="divide-y divide-gray-200">
                    @foreach ($history as $h)
                        <li class="py-4 first:pt-0 last:pb-0">
                            <p class="font-semibold text-sm">{{ $h[0] }} • {{ $h[1]->format('j M, H.i') }}</p>
                            <p class="text-sm text-gray-500 mt-2">{{ $h[2] }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
@endsection
