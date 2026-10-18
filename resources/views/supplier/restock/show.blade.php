@extends('layouts.supplier')

@php
    use App\Models\RawMaterial;
    use App\Models\RestockOrder;
    use Illuminate\Support\Str;

    $rupiah = fn($v) => RestockOrder::rupiah($v);
    $details = $order->details;
    $single = $details->count() === 1 ? $details->first() : null;
    $current = array_search($order->status, RestockOrder::FLOW);
    $location = 'Candi Panggung, Malang';
    $ownerName = $order->owner->nama;

    $badge = [
        'diajukan' => 'bg-amber-50 text-amber-700',
        'dikonfirmasi' => 'bg-sky-50 text-sky-700',
        'dikirim' => 'bg-blue-50 text-blue-700',
        'diterima' => 'bg-teal-50 text-teal-700',
    ];

    $quantityText = $single
        ? RawMaterial::formatNumber($single->jumlah) . ' ' . $single->material->satuan
        : $details->count() . ' items';

    $agreeText = $single
        ? RawMaterial::formatNumber($single->jumlah) .
            ' ' .
            $single->material->satuan .
            ' × ' .
            $rupiah($single->harga_satuan)
        : $details->count() . ' items totaling ' . $rupiah($order->total);

    // Suggested delivery: tomorrow 08:30 (never in the past)
    $suggested = now()->addDay()->setTime(8, 30)->format('Y-m-d\TH:i');

    // Order history, oldest first
    $history = collect([
        ['Submitted', $order->tanggal_pengajuan, $ownerName . ' submitted a restock for the ' . $location . ' branch.'],
        [
            'Confirmed',
            $order->tanggal_dikonfirmasi,
            $order->perkiraan_kirim
                ? 'Confirmed by ' .
                    $supplier->nama_supplier .
                    '. Estimated delivery ' .
                    $order->perkiraan_kirim->format('j M Y, H.i') .
                    '.'
                : 'Confirmed by ' . $supplier->nama_supplier . '.',
        ],
        ['Sent', $order->tanggal_dikirim, 'The shipment is on its way to the branch.'],
        ['Accepted', $order->tanggal_diterima, 'Received by the laundry team.'],
    ])
        ->filter(fn($h) => $h[1] !== null)
        ->values();
@endphp

@section('title', 'Restock Orders')
@section('crumb', 'Restock Orders / ' . $order->kode_restock)
@section('heading', 'Order Detail and Confirmation')
@section('subheading', $order->kode_restock . ' • Submitted by ' . $ownerName . ' on ' .
    $order->tanggal_pengajuan->format('j F Y, H.i'))

@section('actions')
    <a href="{{ route('supplier.restock.index') }}"
        class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg px-5 py-3 text-sm font-semibold">
        <i class="ti ti-arrow-left"></i> Back to List
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
                {{ $index === $current ? 'border-2 border-teal-700 text-teal-700 bg-white font-semibold' : ($index < $current ? 'bg-teal-700 text-white' : 'bg-gray-100 text-gray-500') }}">
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

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Left column --}}
        <div class="xl:col-span-2 space-y-6">
            {{-- Ordered items --}}
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h2 class="text-xl font-semibold">Ordered items</h2>
                <p class="text-sm text-gray-500 mt-1 mb-5">Supplier: {{ $supplier->nama_supplier }} • Order number:
                    {{ $order->kode_restock }}</p>

                <div class="border border-gray-200 rounded-xl overflow-hidden overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[#eef3f2] text-left text-gray-500">
                            <tr>
                                <th class="px-5 py-4 font-medium">Material</th>
                                <th class="px-5 py-4 font-medium">Quantity</th>
                                <th class="px-5 py-4 font-medium">Unit price</th>
                                <th class="px-5 py-4 font-medium">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($details as $d)
                                <tr>
                                    <td class="px-5 py-5 font-semibold">{{ $d->material->nama_bahan }}</td>
                                    <td class="px-5 py-5 whitespace-nowrap">{{ RawMaterial::formatNumber($d->jumlah) }}
                                        {{ $d->material->satuan }}</td>
                                    <td class="px-5 py-5 whitespace-nowrap">{{ $rupiah($d->harga_satuan) }} /
                                        {{ Str::singular($d->material->satuan) }}</td>
                                    <td class="px-5 py-5 whitespace-nowrap font-semibold">{{ $rupiah($d->subtotal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 space-y-3 text-gray-500">
                    <div class="flex justify-between gap-4">
                        <span>Subtotal @if ($single)
                                • {{ $agreeText }}
                            @else
                                • {{ $details->count() }} items
                            @endif
                        </span>
                        <span class="text-[#12332d]">{{ $rupiah($order->total) }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span>Shipping cost</span>
                        <span class="text-[#12332d]">Included</span>
                    </div>
                </div>

                <div class="border-t border-gray-200 mt-5 pt-5 flex items-center justify-between">
                    <span class="text-gray-500">Order total</span>
                    <span class="text-3xl font-bold text-teal-700">{{ $rupiah($order->total) }}</span>
                </div>
            </div>

            {{-- Destination --}}
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h2 class="text-xl font-semibold mb-5">Destination</h2>

                <div class="flex items-start gap-4">
                    <span
                        class="w-12 h-12 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center text-2xl shrink-0">
                        <i class="ti ti-map-pin"></i>
                    </span>
                    <div>
                        <p class="text-lg font-semibold">Mam Express • {{ $location }}</p>
                        <p class="text-gray-500 mt-1">Candi Panggung, Malang, East Java</p>
                        <p class="text-gray-500 mt-1">Recipient: {{ $ownerName }} / laundry team • 08.00–17.00 WIB</p>
                    </div>
                </div>

                <p class="border-t border-gray-200 mt-5 pt-5 text-sm text-gray-500">
                    Hand the materials over to the laundry team to check the quantity and condition of the items.
                </p>
            </div>

            {{-- Note from laundry --}}
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h2 class="text-xl font-semibold mb-5">Note from the laundry</h2>

                @if ($order->catatan)
                    <div class="bg-amber-50 rounded-xl px-5 py-4 flex items-start gap-3">
                        <i class="ti ti-alert-triangle text-2xl text-amber-700"></i>
                        <p class="text-amber-800">{{ $order->catatan }}</p>
                    </div>
                @else
                    <p class="text-gray-400">No notes from the laundry for this order.</p>
                @endif
            </div>
        </div>

        {{-- Right column --}}
        <div class="space-y-6">
            {{-- Confirmation --}}
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h2 class="text-xl font-semibold">Order confirmation</h2>
                <p class="text-sm text-gray-500 mt-1 mb-4">Check material availability and the delivery schedule.</p>

                <span
                    class="inline-block px-3 py-1 rounded-full text-sm {{ $badge[$order->status] }}">{{ $order->status_label }}</span>

                <dl class="mt-5 space-y-4 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Supplier</dt>
                        <dd class="font-semibold text-right">{{ $supplier->nama_supplier }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Quantity</dt>
                        <dd class="font-semibold text-right">{{ $quantityText }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Order value</dt>
                        <dd class="font-semibold text-right">{{ $rupiah($order->total) }}</dd>
                    </div>
                </dl>

                <div class="border-t border-gray-200 mt-5 pt-5">
                    @if ($order->status === 'diajukan')
                        <form method="POST" action="{{ route('supplier.restock.confirm', $order) }}" class="space-y-5">
                            @csrf
                            @method('PATCH')

                            <div>
                                <label for="estimated_delivery" class="block text-sm mb-1.5">Estimated delivery</label>
                                <input type="datetime-local" name="estimated_delivery" id="estimated_delivery"
                                    value="{{ old('estimated_delivery', $suggested) }}"
                                    class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600 @error('estimated_delivery') border-red-500 @enderror">
                                @error('estimated_delivery')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="confirmation_note" class="block text-sm mb-1.5">Confirmation note</label>
                                <textarea name="confirmation_note" id="confirmation_note" rows="3" maxlength="255"
                                    placeholder="{{ $single ? RawMaterial::formatNumber($single->jumlah) . ' ' . $single->material->satuan . ' available. Delivered by internal courier as scheduled.' : 'All items available. Delivered as scheduled.' }}"
                                    class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">{{ old('confirmation_note') }}</textarea>
                                @error('confirmation_note')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="flex items-start gap-3">
                                    <input type="checkbox" name="agree" value="1" @checked(old('agree'))
                                        class="mt-1 w-5 h-5 rounded border-gray-300 text-teal-700 focus:ring-teal-600">
                                    <span>
                                        <span class="block font-semibold">Quantity and price are correct</span>
                                        <span class="block text-sm text-gray-500">I approve {{ $agreeText }}.</span>
                                    </span>
                                </label>
                                @error('agree')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit"
                                class="w-full bg-teal-700 hover:bg-teal-800 text-white font-semibold rounded-lg py-3.5 text-sm">
                                <i class="ti ti-check"></i> Confirm Order
                            </button>

                            <p class="text-xs text-gray-500">The status will change to Confirmed. Shipping details are
                                filled in once the items are ready to ship.</p>
                        </form>
                    @else
                        <dl class="space-y-4 text-sm">
                            <div>
                                <dt class="text-gray-500">Estimated delivery</dt>
                                <dd class="font-semibold mt-1">
                                    {{ $order->perkiraan_kirim ? $order->perkiraan_kirim->format('j M Y, H.i') . ' WIB' : '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Confirmation note</dt>
                                <dd class="mt-1">{{ $order->catatan_konfirmasi ?: '-' }}</dd>
                            </div>
                        </dl>

                        @if ($order->status === 'dikonfirmasi')
                            <a href="{{ route('supplier.shipments.edit', $order) }}"
                                class="mt-5 block w-full text-center bg-teal-700 hover:bg-teal-800 text-white font-semibold rounded-lg py-3.5 text-sm">
                                <i class="ti ti-truck-delivery"></i> Set Shipping
                            </a>
                            <p class="text-xs text-gray-500 mt-3">Fill in the courier and shipping reference, then mark the
                                items as shipped.</p>
                        @elseif (in_array($order->status, ['dikirim', 'diterima']))
                            <a href="{{ route('supplier.shipments.edit', $order) }}"
                                class="mt-5 block w-full text-center bg-white border border-gray-200 hover:bg-gray-50 font-semibold rounded-lg py-3.5 text-sm">
                                <i class="ti ti-truck-delivery"></i> View Shipment
                            </a>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Receipt info --}}
            <div class="bg-teal-50 rounded-2xl px-6 py-5 flex items-start gap-3">
                <i class="ti ti-circle-check text-2xl text-teal-700"></i>
                <div>
                    <p class="font-semibold text-teal-800">Receipt by the laundry</p>
                    <p class="text-sm text-teal-800">Supplier confirmation does not change the status to Accepted.</p>
                </div>
            </div>

            {{-- History --}}
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h2 class="text-xl font-semibold mb-5">Order history</h2>

                <ol class="space-y-5">
                    @foreach ($history as $h)
                        <li>
                            <p class="font-semibold text-sm">{{ $h[0] }} • {{ $h[1]->format('j M, H.i') }}</p>
                            <p class="text-sm text-gray-500 mt-1">{{ $h[2] }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
@endsection
