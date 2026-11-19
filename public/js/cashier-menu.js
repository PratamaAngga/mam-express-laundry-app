(function () {
    var app = document.querySelector('.app');
    var btn = document.querySelector('.menu-btn');
    var sidebar = document.querySelector('.sidebar');
    if (!app || !btn || !sidebar) return;

    app.classList.add('drawer-ready');

    var backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    app.appendChild(backdrop);

    function setOpen(open) {
        app.classList.toggle('sidebar-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.style.overflow = open ? 'hidden' : '';
    }

    btn.addEventListener('click', function () {
        setOpen(!app.classList.contains('sidebar-open'));
    });
    backdrop.addEventListener('click', function () { setOpen(false); });
    sidebar.addEventListener('click', function (e) {
        if (e.target.closest('a')) setOpen(false);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') setOpen(false);
    });
    window.matchMedia('(min-width: 721px)').addEventListener('change', function (e) {
        if (e.matches) setOpen(false);
    });
})();