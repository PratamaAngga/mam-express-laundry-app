(function () {
    'use strict';

    var root = document.getElementById('scanner');
    if (!root) { return; }

    var startBtn = document.getElementById('scanner-start');
    var stopBtn = document.getElementById('scanner-stop');
    var msg = document.getElementById('scanner-msg');
    var orderBase = root.getAttribute('data-order-base');
    var CODE = /BNG-\d{6}-\d{4}/i;
    var scanner = null;
    var handled = false;

    // textContent (bukan innerHTML): isi QR tidak pernah dianggap HTML
    function say(text) { msg.textContent = text; }

    function showRunning(running) {
        startBtn.hidden = running;
        stopBtn.hidden = !running;
    }

    function stopCamera() {
        if (!scanner) { return Promise.resolve(); }
        var s = scanner;
        scanner = null;
        return s.stop().then(function () { s.clear(); }).catch(function () {});
    }

    function onScan(text) {
        if (handled) { return; }

        // Hanya kode pesanan yang diambil dari isi QR; alamat tujuan selalu dibentuk dari halaman ini sendiri
        var found = CODE.exec(text);
        if (!found) {
            say('This QR is not a Mam Express order label.');
            return;
        }

        handled = true;
        say('Order found, opening...');
        stopCamera();
        window.location.href = orderBase + '/' + encodeURIComponent(found[0].toUpperCase());
    }

    startBtn.addEventListener('click', function () {
        if (typeof Html5Qrcode === 'undefined') {
            say('The scanner could not load. Type the order code below instead.');
            return;
        }
        if (!window.isSecureContext) {
            say('The camera needs a secure (HTTPS) connection. Type the order code below instead.');
            return;
        }

        scanner = new Html5Qrcode('scanner-view');
        scanner
            .start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 240, height: 240 } }, onScan, function () {})
            .then(function () {
                showRunning(true);
                say('Point the camera at the QR label.');
            })
            .catch(function () {
                scanner = null;
                say('Could not open the camera. Allow camera access in your browser, or type the order code below.');
            });
    });

    stopBtn.addEventListener('click', function () {
        stopCamera().then(function () {
            showRunning(false);
            say('Camera stopped.');
        });
    });

    window.addEventListener('pagehide', stopCamera);
})();
