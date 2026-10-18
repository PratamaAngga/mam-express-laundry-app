@extends('layouts.processing')

@section('title', 'Work Session - Mam Express')
@section('breadcrumb', 'Work session')

@section('content')
    @if ($shift['started'])

        <div class="page-head">
            <div>
                <h1 id="ws-title">Work session</h1>
                <p class="muted" id="ws-sub">Your shift is active. Scan a basket to update its order status.</p>
            </div>
            <form method="POST" action="{{ route('processing.shift.finish') }}">
                @csrf
                <button type="submit" class="btn-outline btn-sm" id="finish-btn"><x-icon name="log-out" :size="15" /> Finish
                    Shift</button>
            </form>
        </div>

        <div class="ws-wrap" id="ws-root" data-status="{{ $staff['status'] }}"
            data-update-url="{{ route('processing.work-session.update') }}" data-csrf="{{ csrf_token() }}">

            <div class="stepper">
                <span class="step active" data-step="1">Scan basket</span>
                <span class="step" data-step="2">Confirm status</span>
                <span class="step" data-step="3">Update &amp; return</span>
            </div>

            <section class="hero-card ws-card">

                {{-- Pesan sukses (muncul setelah update selesai) --}}
                <div class="ws-banner" id="ws-banner" hidden>
                    <x-icon name="check-circle" :size="20" />
                    <div>
                        <strong id="banner-title"></strong><br>
                        <span class="small">Update complete. You’re back in the scan form automatically.</span>
                    </div>
                </div>

                {{-- Judul tahap scan --}}
                <div class="ws-title-row" id="scan-head">
                    <span class="hero-icon"><x-icon name="scan-line" :size="22" /></span>
                    <div>
                        <h2 id="scan-title">Ready to scan</h2>
                        <p class="muted" id="scan-sub">Scan the QR label attached to the laundry basket.</p>
                    </div>
                </div>

                <div class="info-box">
                    <x-icon name="lock" :size="18" />
                    <div>
                        <strong>Assigned task: {{ $staff['task'] }} &rarr; Status: {{ $staff['status'] }}</strong><br>
                        <span class="small">Set by your signed-in role. No status selection needed.</span>
                    </div>
                </div>

                <div class="ws-label-row">
                    <label for="order-id">Order ID</label>
                    <span class="ws-hint ok" id="ws-hint">Focused · ready for input</span>
                </div>

                {{-- Pemindai kamera --}}
                <div class="ws-camera" id="cam" hidden>
                    <video id="cam-video" playsinline muted></video>
                    <span class="cam-frame"></span>
                    <p class="cam-status" id="cam-status">Starting camera…</p>
                    <button type="button" class="cam-stop" id="cam-stop" aria-label="Stop camera"><x-icon name="x"
                            :size="16" /></button>
                </div>

                <div class="ws-input">
                    <x-icon name="scan-line" :size="20" />
                    <input type="text" id="order-id" autocomplete="off" autocapitalize="characters" spellcheck="false"
                        placeholder="Scan a basket QR or type an order ID" autofocus>
                </div>
                <p class="ws-help" id="ws-help"></p>
                <p class="ws-error" id="ws-error" hidden></p>

                <div class="ws-actions" id="cam-actions">
                    <button type="button" class="btn-outline btn-sm" id="cam-start"><x-icon name="camera" :size="15" /> Scan
                        with camera</button>
                </div>

                {{-- Tahap konfirmasi --}}
                <div id="confirm-block" hidden>
                    <h2 class="ws-confirm-title">Order <span id="confirm-id"></span> will be updated to <span
                            class="accent">{{ $staff['status'] }}</span></h2>
                    <button type="button" class="btn-primary" id="update-btn"><x-icon name="check" :size="16" /> Update
                        Status</button>
                    <p class="ws-note">Nothing changes until you select Update Status.</p>
                </div>

                {{-- Tahap menunggu update --}}
                <div id="updating-block" hidden>
                    <div class="ws-updating"><span class="spinner"></span><span>Updating to {{ $staff['status'] }}…</span></div>
                    <p class="muted">Waiting for the system to save Order <span id="updating-id"></span>. The update is not
                        complete yet.</p>
                    <button type="button" class="btn-primary" disabled>Updating Status…</button>
                    <p class="ws-note">Please don’t scan another basket or leave this screen. The scan form will reopen
                        automatically after the system confirms completion.</p>
                </div>
            </section>

            <p class="ws-foot" id="ws-foot"></p>
        </div>

    @else

        <div class="page-head">
            <div>
                <h1>Work session</h1>
                <p class="muted">Scan and confirm each basket as you work.</p>
            </div>
        </div>

        <section class="hero-card">
            <span class="hero-icon"><x-icon name="scan-line" :size="22" /></span>
            <h2>Your shift has not started yet</h2>
            <p class="muted">Start your shift from the Home page before scanning baskets.</p>
            <a href="{{ route('processing.home') }}" class="btn-primary"><x-icon name="arrow-right" :size="16" /> Go to Home</a>
        </section>

    @endif
@endsection

@if ($shift['started'])
    @push('scripts')
        <script src="{{ asset('js/vendor/jsQR.js') }}"></script>
        <script>
            @verbatim
                (function () {
                    var root = document.getElementById('ws-root');
                    var STATUS = root.dataset.status;
                    var UPDATE_URL = root.dataset.updateUrl;
                    var CSRF = root.dataset.csrf;
                    var ID_RE = /^[A-Z]{2,5}-\d{3,6}(-\d{3,6})?$/;

                    var el = {
                        title: document.getElementById('ws-title'),
                        sub: document.getElementById('ws-sub'),
                        foot: document.getElementById('ws-foot'),
                        banner: document.getElementById('ws-banner'),
                        bannerTitle: document.getElementById('banner-title'),
                        scanHead: document.getElementById('scan-head'),
                        scanTitle: document.getElementById('scan-title'),
                        scanSub: document.getElementById('scan-sub'),
                        hint: document.getElementById('ws-hint'),
                        input: document.getElementById('order-id'),
                        help: document.getElementById('ws-help'),
                        error: document.getElementById('ws-error'),
                        confirmBlock: document.getElementById('confirm-block'),
                        confirmId: document.getElementById('confirm-id'),
                        updatingBlock: document.getElementById('updating-block'),
                        updatingId: document.getElementById('updating-id'),
                        updateBtn: document.getElementById('update-btn'),
                        finishBtn: document.getElementById('finish-btn'),
                        steps: document.querySelectorAll('.step'),
                        cam: document.getElementById('cam'),
                        camVideo: document.getElementById('cam-video'),
                        camStatus: document.getElementById('cam-status'),
                        camStart: document.getElementById('cam-start'),
                        camStop: document.getElementById('cam-stop'),
                        camActions: document.getElementById('cam-actions')
                    };

                    var TEXT = {
                        scan: {
                            title: 'Work session',
                            sub: 'Your shift is active. Scan a basket to update its order status.',
                            help: 'No click needed. Scanning fills the order ID and opens confirmation automatically. For typed IDs, press Enter.',
                            foot: 'Keep scanning throughout your shift. Use Finish Shift only when you’re done.'
                        },
                        scanAfter: {
                            foot: 'Scan → confirm → ready to scan. No need to start work again.'
                        },
                        confirm: {
                            title: 'Confirm status update',
                            sub: 'The basket was scanned. Review the order before updating its status.',
                            help: 'The basket QR filled the order ID and opened confirmation automatically.',
                            foot: 'After the update completes, you’ll return to an empty, focused Order ID field.'
                        },
                        updating: {
                            title: 'Updating order',
                            sub: 'Your confirmation was sent. Please wait for the update to complete.',
                            help: 'The basket QR filled the order ID and opened confirmation automatically.',
                            foot: 'Finish Shift will be available again when this update completes.'
                        }
                    };

                    var state = 'scan';
                    var orderId = '';

                    function showError(message) {
                        el.error.textContent = message;
                        el.error.hidden = false;
                    }

                    function clearError() {
                        el.error.hidden = true;
                        el.error.textContent = '';
                    }

                    function setState(next, opts) {
                        opts = opts || {};
                        state = next;
                        var t = TEXT[next];
                        var isScan = next === 'scan';

                        el.title.textContent = t.title;
                        el.sub.textContent = t.sub;
                        el.help.textContent = t.help;
                        el.foot.textContent = isScan && opts.after ? TEXT.scanAfter.foot : t.foot;

                        var stepNumber = isScan ? 1 : (next === 'confirm' ? 2 : 3);
                        el.steps.forEach(function (step) {
                            step.classList.toggle('active', Number(step.dataset.step) === stepNumber);
                        });

                        el.banner.hidden = !(isScan && opts.after);
                        if (isScan && opts.after) {
                            el.bannerTitle.textContent = 'Order ' + opts.orderId + ' updated to ' + STATUS + '.';
                        }

                        el.scanTitle.textContent = opts.after ? 'Ready for the next basket' : 'Ready to scan';
                        el.scanSub.textContent = opts.after
                            ? 'The Order ID field is cleared and focused again.'
                            : 'Scan the QR label attached to the laundry basket.';
                        el.scanHead.hidden = !isScan;

                        el.hint.textContent = isScan ? 'Focused · ready for input' : 'From basket QR';
                        el.hint.className = 'ws-hint' + (isScan ? ' ok' : '');

                        el.input.readOnly = !isScan;
                        el.confirmBlock.hidden = next !== 'confirm';
                        el.updatingBlock.hidden = next !== 'updating';
                        el.finishBtn.disabled = next === 'updating';
                        el.camActions.hidden = !isScan || camActive;
                        if (!isScan) stopCamera();

                        if (isScan) {
                            el.input.value = '';
                            if (cameraWanted) {
                                startCamera();
                            } else {
                                el.input.focus();
                            }
                        } else if (next === 'confirm') {
                            el.updateBtn.focus();
                        }
                    }

                    // Tahap 1 -> 2: scanner mengetik ID lalu menekan Enter
                    function handleScan() {
                        var id = el.input.value.trim().toUpperCase();
                        if (!id) return;

                        if (!ID_RE.test(id)) {
                            showError('Order ID not recognised. Check the label and try again.');
                            el.input.select();
                            return;
                        }

                        clearError();
                        orderId = id;
                        el.input.value = id;
                        el.confirmId.textContent = id;
                        el.updatingId.textContent = id;
                        setState('confirm');
                    }

                    el.input.addEventListener('keydown', function (e) {
                        if (state === 'scan' && e.key === 'Enter') {
                            e.preventDefault();
                            handleScan();
                        }
                    });

                    el.input.addEventListener('input', function () {
                        if (state === 'scan') clearError();
                    });

                    // Tahap 2 -> 3 -> 1
                    el.updateBtn.addEventListener('click', function () {
                        clearError();
                        setState('updating');
                        var startedAt = Date.now();

                        function finish(callback) {
                            // Tahap "Updating" ditahan minimal 1,2 detik supaya terlihat jelas
                            setTimeout(callback, Math.max(0, 1200 - (Date.now() - startedAt)));
                        }

                        fetch(UPDATE_URL, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': CSRF
                            },
                            body: JSON.stringify({ order_id: orderId })
                        })
                            .then(function (response) {
                                return response.json().then(function (data) {
                                    return { ok: response.ok && data.ok, data: data };
                                });
                            })
                            .then(function (result) {
                                finish(function () {
                                    if (result.ok) {
                                        setState('scan', { after: true, orderId: orderId });
                                    } else {
                                        setState('confirm');
                                        showError(result.data.message || 'The update could not be completed. Please try again.');
                                    }
                                });
                            })
                            .catch(function () {
                                finish(function () {
                                    setState('confirm');
                                    showError('Could not reach the server. Please try again.');
                                });
                            });
                    });

                    // Kolom Order ID selalu siap menerima scan
                    document.addEventListener('click', function (e) {
                        if (state === 'scan' && !camActive && !e.target.closest('button, a, form')) {
                            el.input.focus();
                        }
                    });

                    // ---- Scan lewat kamera ----
                    var cameraWanted = false;
                    var camActive = false;
                    var camStream = null;
                    var camTimer = null;
                    var lastBad = '';
                    var lastBadAt = 0;
                    var detector = null;
                    var canvas = document.createElement('canvas');
                    var ctx = canvas.getContext('2d', { willReadFrequently: true });

                    // Ingat pilihan kamera, supaya kamera menyala otomatis di scan berikutnya
                    try { cameraWanted = window.localStorage.getItem('ws_camera') === '1'; } catch (e) { }
                    function rememberCamera(on) {
                        try { window.localStorage.setItem('ws_camera', on ? '1' : '0'); } catch (e) { }
                    }

                    // Pakai pembaca bawaan browser kalau ada, kalau tidak pakai jsQR
                    if ('BarcodeDetector' in window) {
                        try { detector = new BarcodeDetector({ formats: ['qr_code'] }); } catch (e) { detector = null; }
                    }

                    function cameraError(err) {
                        var name = err && err.name;
                        if (name === 'NotAllowedError' || name === 'SecurityError') {
                            return 'Camera access was blocked. Allow camera access in your browser, or type the order ID.';
                        }
                        if (name === 'NotFoundError' || name === 'OverconstrainedError') {
                            return 'No camera was found on this device. Type the order ID instead.';
                        }
                        if (name === 'NotReadableError') {
                            return 'The camera is being used by another app. Close it and try again.';
                        }
                        return 'The camera could not be started. Type the order ID instead.';
                    }

                    function startCamera() {
                        if (camActive) return;

                        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.isSecureContext) {
                            cameraWanted = false;
                            rememberCamera(false);
                            showError('Camera scanning needs a secure connection (HTTPS or localhost). Type the order ID instead.');
                            el.input.focus();
                            return;
                        }
                        if (!detector && typeof window.jsQR !== 'function') {
                            cameraWanted = false;
                            showError('The QR reader file is missing (public/js/vendor/jsQR.js). Type the order ID instead.');
                            el.input.focus();
                            return;
                        }

                        clearError();
                        cameraWanted = true;
                        rememberCamera(true);
                        camActive = true;
                        el.cam.hidden = false;
                        el.camActions.hidden = true;
                        el.camStatus.textContent = 'Starting camera…';
                        el.hint.textContent = 'Camera on · scanning';

                        navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false })
                            .then(function (stream) {
                                if (!camActive) {
                                    stream.getTracks().forEach(function (track) { track.stop(); });
                                    return null;
                                }
                                camStream = stream;
                                el.camVideo.srcObject = stream;
                                return el.camVideo.play();
                            })
                            .then(function () {
                                if (camActive) {
                                    el.camStatus.textContent = 'Point the camera at the QR label.';
                                    scanLoop();
                                }
                            })
                            .catch(function (err) {
                                cameraWanted = false;
                                rememberCamera(false);
                                stopCamera();
                                showError(cameraError(err));
                                el.input.focus();
                            });
                    }

                    function stopCamera() {
                        camActive = false;
                        clearTimeout(camTimer);
                        if (camStream) {
                            camStream.getTracks().forEach(function (track) { track.stop(); });
                            camStream = null;
                        }
                        el.camVideo.srcObject = null;
                        el.cam.hidden = true;
                        el.camActions.hidden = state !== 'scan';
                        if (state === 'scan') el.hint.textContent = 'Focused · ready for input';
                    }

                    function scanLoop() {
                        if (!camActive) return;

                        function next() { camTimer = setTimeout(scanLoop, 150); }

                        var video = el.camVideo;
                        if (video.readyState < 2 || !video.videoWidth) { next(); return; }

                        var reading;
                        if (detector) {
                            reading = detector.detect(video).then(function (codes) {
                                return codes.length ? codes[0].rawValue : null;
                            });
                        } else {
                            var scale = Math.min(1, 640 / video.videoWidth);
                            canvas.width = Math.round(video.videoWidth * scale);
                            canvas.height = Math.round(video.videoHeight * scale);
                            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                            var image = ctx.getImageData(0, 0, canvas.width, canvas.height);
                            var code = window.jsQR(image.data, image.width, image.height, { inversionAttempts: 'dontInvert' });
                            reading = Promise.resolve(code ? code.data : null);
                        }

                        reading.then(function (value) {
                            if (!camActive) return;
                            if (value) onCode(value);
                            if (camActive) next();
                        }).catch(function () {
                            if (camActive) next();
                        });
                    }

                    // QR boleh berisi kode order saja, atau teks/URL yang memuat kode order
                    function onCode(value) {
                        var match = String(value).toUpperCase().match(/[A-Z]{2,5}-\d{3,6}(-\d{3,6})?/);

                        if (!match) {
                            var now = Date.now();
                            if (value !== lastBad || now - lastBadAt > 3000) {
                                showError('That QR code is not a Mam Express order label.');
                                lastBad = value;
                                lastBadAt = now;
                            }
                            return;
                        }

                        clearError();
                        if (navigator.vibrate) navigator.vibrate(120);
                        stopCamera();
                        el.input.value = match[0];
                        handleScan();
                    }

                    el.camStart.addEventListener('click', startCamera);
                    el.camStop.addEventListener('click', function () {
                        cameraWanted = false;
                        rememberCamera(false);
                        stopCamera();
                        el.input.focus();
                    });

                    // Matikan kamera saat tab disembunyikan, nyalakan lagi saat kembali
                    document.addEventListener('visibilitychange', function () {
                        if (document.hidden) {
                            if (camActive) stopCamera();
                        } else if (state === 'scan' && cameraWanted && !camActive) {
                            startCamera();
                        }
                    });
                    window.addEventListener('pagehide', stopCamera);

                    setState('scan');
                })();
            @endverbatim
        </script>
    @endpush
@endif