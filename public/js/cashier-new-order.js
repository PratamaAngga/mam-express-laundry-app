// Logika tampilan halaman New Order (tanpa library, murni JavaScript)
(function () {
    const form = document.getElementById('order-form');
    const FRESHENER = parseInt(form.dataset.freshener, 10);

    const wa = document.getElementById('whatsapp');
    const waHint = document.getElementById('wa-hint');
    const waValid = document.getElementById('wa-valid');
    const waValidNumber = document.getElementById('wa-valid-number');
    const nameInput = document.getElementById('customer_name');
    const customerMeta = document.getElementById('customer-meta');
    const weight = document.getElementById('weight');
    const fresh = document.getElementById('freshener');

    // DATA CONTOH. Nanti diganti dengan fetch() ke endpoint pelanggan buatan backend.
    const dummyCustomers = {
        '085856170561': { name: 'Siti Rahma', meta: 'Customer since May 2026 • 12 orders' },
    };

    const rupiah = (n) => 'Rp' + Math.round(n).toLocaleString('id-ID');
    const digits = (s) => s.replace(/\D/g, '');
    const formatPhone = (d) => d.replace(/(\d{4})(?=\d)/g, '$1 ').trim();

    function checkCustomer() {
        const d = digits(wa.value);
        const valid = /^(0|62)8\d{8,11}$/.test(d);
        waValid.hidden = !valid;
        wa.classList.toggle('is-valid', valid);
        document.getElementById('wa-check').hidden = !valid;
        waValidNumber.textContent = formatPhone(d);

        const found = dummyCustomers[d];
        if (found) {
            nameInput.value = found.name;
            nameInput.readOnly = true;
            customerMeta.textContent = found.meta;
            waHint.textContent = 'Customer found. The name is filled in automatically.';
            waHint.className = 'hint ok';
        } else {
            nameInput.readOnly = false;
            customerMeta.textContent = '';
            waHint.textContent = d.length > 5 ? 'Number not registered yet. Enter a name or click + New Customer.' : '';
            waHint.className = 'hint';
        }
    }

    function updatePrice() {
        const service = form.querySelector('input[name="service"]:checked');
        const kg = parseFloat(weight.value.replace(',', '.')) || 0;
        const price = parseInt(service.dataset.price, 10);
        const serviceTotal = kg * price;
        const total = serviceTotal + (fresh.checked ? FRESHENER : 0);

        document.getElementById('sum-service').textContent = service.dataset.name;
        document.getElementById('sum-calc').textContent = String(kg).replace('.', ',') + ' kg × ' + rupiah(price);
        document.getElementById('sum-service-total').textContent = rupiah(serviceTotal);
        document.getElementById('sum-fresh-row').hidden = !fresh.checked;
        document.getElementById('sum-fresh').textContent = rupiah(FRESHENER);
        document.getElementById('sum-total').textContent = rupiah(total);

        const eta = new Date();
        eta.setDate(eta.getDate() + parseInt(service.dataset.days, 10));
        const date = eta.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        const time = eta.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace(':', '.');
        document.getElementById('sum-eta').textContent = date + ', ' + time;

        // Tandai kartu yang dipilih
        form.querySelectorAll('.option').forEach(function (el) {
            el.classList.toggle('selected', el.querySelector('input').checked);
        });
    }

    wa.addEventListener('input', checkCustomer);
    weight.addEventListener('input', updatePrice);
    fresh.addEventListener('change', updatePrice);
    form.querySelectorAll('input[name="service"]').forEach(function (r) {
        r.addEventListener('change', updatePrice);
    });

    checkCustomer();
    updatePrice();
})();