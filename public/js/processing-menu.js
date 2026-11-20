/* Sidebar processing jadi drawer di layar kecil (tombol ☰), sama seperti pola di Owner. */
(function () {
    var app = document.querySelector('.app');
    var btn = document.querySelector('.menu-btn');
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.querySelector('.sidebar-backdrop');
    if (!app || !btn || !sidebar || !backdrop) return;

    // Harus sama dengan breakpoint di processing.css (drawer aktif di bawah 1024px)
    var desktop = window.matchMedia('(min-width: 1024px)');

    function setOpen(open) {
        app.classList.toggle('sidebar-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.classList.toggle('no-scroll', open);
    }

    btn.addEventListener('click', function () {
        setOpen(!app.classList.contains('sidebar-open'));
    });
    backdrop.addEventListener('click', function () { setOpen(false); });

    // Tutup drawer saat pindah halaman. Tombol logout sengaja tidak menutup drawer
    // karena dialog konfirmasinya berada di dalam sidebar.
    sidebar.addEventListener('click', function (e) {
        if (e.target.closest('a')) setOpen(false);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && app.classList.contains('sidebar-open')) {
            setOpen(false);
            btn.focus();
        }
    });

    desktop.addEventListener('change', function (e) {
        if (e.matches) setOpen(false);
    });
})();