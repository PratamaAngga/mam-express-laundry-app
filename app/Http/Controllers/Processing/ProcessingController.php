<?php

namespace App\Http\Controllers\Processing;

use App\Http\Controllers\Controller;
use Carbon\Carbon;

class ProcessingController extends Controller
{
    private const SHIFT_KEY = 'processing_shift_started_at';

    // Halaman Processing home
    public function home()
    {
        return view('processing.home', $this->data());
    }

    // Halaman Work session (placeholder sampai mockup scanner dikirim)
    public function workSession()
    {
        return view('processing.work-session', $this->data());
    }

    // Tombol "Start Work"
    public function startShift()
    {
        // TODO (backend): simpan waktu mulai shift ke database
        session([self::SHIFT_KEY => Carbon::now()->toIso8601String()]);

        return redirect()->route('processing.work-session');
    }

    // Tombol "Finish Shift"
    public function finishShift()
    {
        session()->forget(self::SHIFT_KEY);

        return redirect()->route('processing.home');
    }

    private function data(): array
    {
        // Nama & inisial diambil dari user yang login; 'task' dan 'status' masih dummy
        $nama = trim(auth()->user()->nama);
        $words = preg_split('/\s+/', $nama);
        $staff = [
            'name' => $nama,
            'first_name' => $words[0],
            'initials' => collect($words)->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode(''),
            'role' => 'Processing',
            'task' => 'Washing',
            'status' => 'Washing',
        ];

        $startedAt = session(self::SHIFT_KEY);
        $started = $startedAt ? Carbon::parse($startedAt) : null;

        return [
            'staff' => $staff,
            'shift' => [
                'hours' => '08:00 – 16:00',
                'started' => $started !== null,
                'started_at' => $started ? $started->format('H:i') : null,
            ],
        ];
    }
}