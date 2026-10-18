<?php

namespace App\Http\Controllers\Processing;

use App\Exceptions\InvalidStatusTransition;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Endpoint JSON untuk halaman Work Session: scan QR -> cek pesanan (lookup) -> konfirmasi (update).
 * Logika status ada di OrderStatusService; controller ini hanya validasi dan membentuk respons.
 */
class ProcessingOrderController extends Controller
{
    private const ID_PATTERN = '/^[A-Za-z]{2,5}-\d{3,6}(-\d{3,6})?$/';

    // GET: status pesanan sekarang dan aksi berikutnya (untuk tahap konfirmasi)
    public function lookup(Request $request, string $code): JsonResponse
    {
        if ($denied = $this->shiftNotStarted()) {
            return $denied;
        }

        if (! preg_match(self::ID_PATTERN, $code)) {
            return $this->invalidId();
        }

        $order = Order::with(['customer', 'service'])->where('kode_pesanan', strtoupper($code))->first();
        if (! $order) {
            return $this->notFound();
        }

        $next = OrderStatusService::next($order->status);
        $reason = OrderStatusService::blockedReason($request->user()->role, $order->status);

        return response()->json([
            'ok' => true,
            'order_id' => $order->kode_pesanan,
            'customer' => $order->customer->nama,
            'service' => $order->service->nama_layanan,
            'weight_kg' => Order::kg($order->berat_kg),
            'status' => $order->status,
            'status_label' => Order::STATUS_LABELS[$order->status] ?? $order->status,
            'next_status' => $next,
            'next_label' => $next ? Order::STATUS_LABELS[$next] : null,
            'can_advance' => $reason === null,
            'message' => $reason,
        ]);
    }

    // POST: majukan status satu langkah. Opsional kirim "from" (status yang dilihat petugas) sebagai pengaman.
    public function update(Request $request, OrderStatusService $statuses): JsonResponse
    {
        if ($denied = $this->shiftNotStarted()) {
            return $denied;
        }

        $validator = Validator::make($request->all(), [
            'order_id' => ['required', 'string', 'max:30', 'regex:' . self::ID_PATTERN],
            'from' => ['nullable', 'string', Rule::in(array_keys(Order::STATUS_LABELS))],
        ]);

        if ($validator->fails()) {
            return $this->invalidId();
        }

        try {
            $order = $statuses->advance(
                $request->user(),
                strtoupper($request->input('order_id')),
                $request->input('from'),
            );
        } catch (ModelNotFoundException $e) {
            return $this->notFound();
        } catch (InvalidStatusTransition $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 409);
        }

        $label = Order::STATUS_LABELS[$order->status];

        return response()->json([
            'ok' => true,
            'order_id' => $order->kode_pesanan,
            'status' => $order->status,
            'status_label' => $label,
            'message' => "Order {$order->kode_pesanan} is now {$label}.",
        ]);
    }

    private function shiftNotStarted(): ?JsonResponse
    {
        if (! session(ProcessingController::SHIFT_KEY)) {
            return response()->json(['ok' => false, 'message' => 'Your shift has not started yet.'], 403);
        }

        return null;
    }

    private function invalidId(): JsonResponse
    {
        return response()->json(['ok' => false, 'message' => 'Order ID not recognised. Check the label and try again.'], 422);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['ok' => false, 'message' => 'Order not found. Check the label and try again.'], 404);
    }
}
