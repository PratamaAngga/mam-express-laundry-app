<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\AdditionalService;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServicePricingController extends Controller
{
    /** Sample weight used by the "Cashier's calculation" card. */
    public const SAMPLE_WEIGHT = 4.5;

    /**
     * Service Pricing: key services, additional services, and the edit panel.
     */
    public function index(Request $request)
    {
        $services   = Service::orderBy('id_layanan')->get();
        $additional = AdditionalService::orderBy('id_tambahan')->get();

        // ?new=1 -> "add service" mode; otherwise edit the selected service (default: the first one)
        $editing = $request->boolean('new')
            ? null
            : ($services->firstWhere('id_layanan', (int) $request->query('edit')) ?? $services->first());

        return view('owner.pricing.index', [
            'services'   => $services,
            'additional' => $additional,
            'editing'    => $editing,
            'weight'     => self::SAMPLE_WEIGHT,
        ]);
    }

    public function store(Request $request)
    {
        $service = Service::create($request->validate($this->rules()));

        return redirect()
            ->route('owner.pricing.index', ['edit' => $service->id_layanan])
            ->with('status', $service->nama_layanan . ' has been added.');
    }

    public function update(Request $request, Service $service)
    {
        // Orders that are already saved keep their own total, so only new orders use this price.
        $service->update($request->validate($this->rules($service->id_layanan)));

        return redirect()
            ->route('owner.pricing.index', ['edit' => $service->id_layanan])
            ->with('status', 'The price of ' . $service->nama_layanan . ' has been applied to new orders.');
    }

    public function updateAdditional(Request $request)
    {
        $data = $request->validate([
            'prices'   => ['required', 'array'],
            'prices.*' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);

        $items = AdditionalService::whereIn('id_tambahan', array_keys($data['prices']))->get();

        foreach ($items as $item) {
            $item->update(['harga' => $data['prices'][$item->id_tambahan]]);
        }

        return back()->with('status', 'Additional service prices have been saved.');
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'nama_layanan'  => ['required', 'string', 'max:50', Rule::unique('jenis_layanan', 'nama_layanan')->ignore($ignoreId, 'id_layanan')],
            'harga_per_kg'  => ['required', 'integer', 'min:0', 'max:1000000'],
            'estimasi_hari' => ['required', 'integer', 'min:1', 'max:30'],
            'status_aktif'  => ['required', 'boolean'],
        ];
    }
}
