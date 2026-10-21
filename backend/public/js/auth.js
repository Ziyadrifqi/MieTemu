
(() => {
    'use strict';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.togglePassword);
            const show = input.type === 'password';

            input.type = show ? 'text' : 'password';
            btn.setAttribute(
                'aria-label',
                show ? 'Sembunyikan password' : 'Tampilkan password'
            );
        });
    });

    document.querySelectorAll('form[data-ajax]').forEach((form) => {
        const msg = form.querySelector('[data-msg]');
        const btn = form.querySelector('button[type="submit"]');

        const show = (text, type = '') => {
            msg.className = ('msg ' + type).trim();
            msg.textContent = text;
        };

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            btn.disabled = true;
            btn.classList.add('loading');
            show('');

            let redirecting = false;

            try {
                const res = await fetch(form.dataset.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(Object.fromEntries(new FormData(form))),
                });

                const data = await res.json().catch(() => ({}));

                if (res.ok) {
                    if (data.redirect) {
                        redirecting = true;
                        window.location.href = data.redirect;
                        return;
                    }

                    show(data.message || 'Berhasil.', 'ok');

                    if (form.hasAttribute('data-hide-on-success')) {
                        form.querySelectorAll('.field, .submit-btn').forEach((node) => {
                            node.hidden = true;
                        });
                    }
                } else if (res.status === 422) {
                    show(
                        Object.values(data.errors || {}).flat().join(' ') ||
                        data.message ||
                        'Data tidak valid.',
                        'err'
                    );
                } else if (res.status === 429) {
                    show('Terlalu banyak percobaan. Coba lagi sebentar lagi.', 'err');
                } else if (res.status === 419) {
                    show(
                        'Halaman sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.',
                        'err'
                    );
                } else {
                    show(data.message || 'Terjadi kesalahan.', 'err');
                }
            } catch (err) {
                show('Tidak bisa terhubung ke server.', 'err');
            } finally {
                if (!redirecting) {
                    btn.disabled = false;
                    btn.classList.remove('loading');
                }
            }
        });
    });
})();
