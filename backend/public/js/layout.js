// Perilaku yang dipakai semua halaman staf: dialog konfirmasi, konfirmasi keluar, dan auto-logout saat idle.
(() => {
    'use strict';

    const $ = (id) => document.getElementById(id);

    const IDLE_MS = (parseInt(document.body.dataset.idleMinutes, 10) || 15) * 60 * 1000;
    const WARN_MS = 60 * 1000;
    const ACTIVITY_KEY = 'mietemu.lastActivity';
    const ACTIVITY_EVENTS = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart'];

    const store = {
        get(key) { try { return localStorage.getItem(key); } catch { return null; } },
        set(key, value) { try { localStorage.setItem(key, value); } catch { /* abaikan */ } },
    };

    // ---------- Dialog konfirmasi (dipakai juga oleh dashboard.js lewat window.MieTemu) ----------
    function confirmBox(message, okLabel = 'Ya, lanjutkan') {
        return new Promise((resolve) => {
            const dlg = $('confirm-dialog');
            $('confirm-message').textContent = message;
            $('confirm-ok').textContent = okLabel;
            dlg.returnValue = '';
            dlg.addEventListener('close', () => resolve(dlg.returnValue === 'ok'), { once: true });
            dlg.showModal();
        });
    }

        // ---------- Alert (toast) berwaktu WIB ----------
    const fmtClock = new Intl.DateTimeFormat('en-GB', {
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: 'Asia/Jakarta',
    });

    function toast(message, type = '') {
        const node = document.createElement('div');
        node.className = ('toast ' + type).trim();
        node.setAttribute('role', type === 'err' ? 'alert' : 'status');

        const text = document.createElement('span');
        text.className = 'toast-text';
        text.textContent = message;

        const time = document.createElement('time');
        time.className = 'toast-time';
        time.textContent = `${fmtClock.format(new Date())} WIB`;

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'toast-close';
        close.setAttribute('aria-label', 'Tutup');
        close.textContent = '\u00d7';
        close.addEventListener('click', () => node.remove());

        node.append(text, time, close);
        $('toasts').append(node);
        setTimeout(() => node.remove(), type === 'err' ? 8000 : 5000);
    }

    window.MieTemu = { confirm: confirmBox, toast };

    // ---------- Keluar ----------
    const logoutForm = $('logout-form');
    let loggingOut = false;

    function logout(reason = '') {
        if (loggingOut || !logoutForm) return;
        loggingOut = true;
        $('logout-reason').value = reason;
        logoutForm.submit(); // submit() langsung tidak memicu event 'submit' lagi
    }

    logoutForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (await confirmBox('Yakin ingin keluar dari akun ini?', 'Ya, keluar')) logout();
    });

    // ---------- Auto-logout saat tidak ada aktivitas ----------
    if (!logoutForm) return;

    const idleDialog = $('idle-dialog');
    let lastActivity = Date.now();

    const sharedLastActivity = () => Math.max(lastActivity, Number(store.get(ACTIVITY_KEY)) || 0);

    function touch() {
        lastActivity = Date.now();
        store.set(ACTIVITY_KEY, String(lastActivity));
    }

    const warningOpen = () => idleDialog.open;

    // Saat peringatan tampil, hanya tombol di dialog yang dihitung sebagai aktivitas.
    ACTIVITY_EVENTS.forEach((name) => document.addEventListener(name, () => {
        if (warningOpen()) return;
        if (Date.now() - lastActivity > 2000) touch(); // dibatasi, tidak menulis tiap gerakan mouse
    }, { passive: true, capture: true }));

    idleDialog.addEventListener('close', touch); // "Tetap masuk" atau tombol Esc
    $('idle-logout').addEventListener('click', () => logout());

    function tick() {
        const remaining = IDLE_MS - (Date.now() - sharedLastActivity());

        if (remaining <= 0) return logout('idle');

        if (remaining <= WARN_MS) {
            if (!idleDialog.open) idleDialog.showModal();
            $('idle-seconds').textContent = String(Math.ceil(remaining / 1000));
        } else if (idleDialog.open) {
            idleDialog.close(); // tab lain sudah aktif lagi
        }
    }

    touch(); // membuka halaman dihitung sebagai aktivitas
    setInterval(tick, 1000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) tick(); }); // setelah laptop tidur
})();