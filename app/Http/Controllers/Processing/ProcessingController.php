<?php

namespace App\Http\Controllers\Processing;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProcessingController extends Controller
{
    private const SHIFT_KEY = 'processing_shift_started_at';

    // Halaman Processing home
    public function home()
    {
        return view('processing.home', $this->data());
    }

    // Halaman Work session (scan -> konfirmasi -> update)
    public function workSession()
    {
        return view('processing.work-session', $this->data());
    }

    // Tombol "Start Work"
    public function startShift()
    {
        session([self::SHIFT_KEY => Carbon::now()->toIso8601String()]);

        return redirect()->route('processing.work-session');
    }

    // Tombol "Finish Shift"
    public function finishShift()
    {
        session()->forget(self::SHIFT_KEY);

        return redirect()->route('processing.home');
    }

    // Tombol "Update Status" (dipanggil lewat fetch dari halaman Work session)
    public function updateStatus(Request $request)
    {
        if (!session(self::SHIFT_KEY)) {
            return response()->json(['ok' => false, 'message' => 'Your shift has not started yet.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'order_id' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z]{2,5}-\d{3,6}(-\d{3,6})?$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Order ID not recognised. Check the label and try again.',
            ], 422);
        }

        $orderId = strtoupper($request->input('order_id'));
        $staff = $this->staff();

        // TODO (backend): cari order di database, ubah statusnya sesuai tugas staf
        $log = session('processing_updates', []);
        $log[] = [
            'order_id' => $orderId,
            'status' => $staff['status'],
            'by' => $staff['name'],
            'at' => Carbon::now()->toIso8601String(),
        ];
        session(['processing_updates' => $log]);

        return response()->json([
            'ok' => true,
            'order_id' => $orderId,
            'status' => $staff['status'],
        ]);
    }

    // DATA DUMMY
    private function staff(): array
    {
        return [
            'name' => 'Adi Maulana',
            'first_name' => 'Adi',
            'initials' => 'AP',
            'role' => 'Processing',
            'task' => 'Washing',
            'status' => 'Washing',
        ];
    }

    private function data(): array
    {
        $startedAt = session(self::SHIFT_KEY);
        $started = $startedAt ? Carbon::parse($startedAt) : null;

        return [
            'staff' => $this->staff(),
            'shift' => [
                'hours' => '08:00 – 16:00',
                'started' => $started !== null,
                'started_at' => $started ? $started->format('H:i') : null,
            ],
        ];
    }
}