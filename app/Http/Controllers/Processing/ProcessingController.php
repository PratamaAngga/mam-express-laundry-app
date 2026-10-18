<?php

namespace App\Http\Controllers\Processing;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProcessingController extends Controller
{
    public const SHIFT_KEY = 'processing_shift_started_at';

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

    // DATA DUMMY
    private function staff(): array
    {
        // Nama dan inisial diambil dari akun yang sedang login
        $nama = trim(auth()->user()->nama);
        $words = preg_split('/\s+/', $nama);

        return [
            'name' => $nama,
            'first_name' => $words[0],
            'initials' => collect($words)->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode(''),
            'role' => 'Processing',
            // TODO (backend): 'task' dan 'status' masih dummy, diganti saat updateStatus tersambung ke database
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