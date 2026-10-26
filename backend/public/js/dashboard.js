(() => {
    'use strict';

    const API = '/staff-api';
    const POLL_MS = 10000;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    const STATUS = {
        waiting: 'Menunggu',
        called: 'Dipanggil',
        seated: 'Sedang makan',
        completed: 'Selesai',
        cancelled: 'Dibatalkan',
        no_show: 'Tidak hadir',
    };
    const TABLE_STATUS = {
        available: 'Kosong',
        allocated: 'Dialokasikan',
        occupied: 'Terisi',
        needs_cleaning: 'Perlu dibersihkan',
        inactive: 'Nonaktif',
    };
    const ROLE = { admin: 'Admin', host: 'Host', kitchen: 'Dapur' };

    // Tombol aksi per status tiket. 'confirm' = tindakan penting, minta konfirmasi dulu.
    const ACTIONS = {
        waiting: [
            { key: 'call', label: 'Panggil', style: 'primary', done: 'dipanggil' },
            { key: 'cancel', label: 'Batalkan', style: 'danger', done: 'dibatalkan', confirm: (t) => `Batalkan tiket ${t.code} (${t.customer_name})?` },
        ],
        called: [
            { key: 'seat', label: 'Dudukkan', style: 'primary', custom: 'seat' },
            { key: 'no-show', label: 'Tidak hadir', style: 'ghost', done: 'ditandai tidak hadir', confirm: (t) => `Tandai ${t.code} (${t.customer_name}) sebagai tidak hadir?` },
            { key: 'cancel', label: 'Batalkan', style: 'danger', done: 'dibatalkan', confirm: (t) => `Batalkan tiket ${t.code} (${t.customer_name})?` },
        ],
        seated: [
            { key: 'complete', label: 'Selesai', style: 'primary', done: 'selesai', confirm: (t) => `Tandai kunjungan ${t.code} (${t.customer_name}) selesai? Meja akan ditandai perlu dibersihkan.` },
        ],
    };

    const state = {
        branches: [], branch: null, view: 'queue',
        status: 'waiting', page: 1, lastPage: 1, tickets: [], summary: {}, sessionDate: null,
        tables: [], tableSummary: {}, editingTable: null,
        busy: false,
    };

    const $ = (id) => document.getElementById(id);
    const fmtTime = new Intl.DateTimeFormat('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false, timeZone: 'Asia/Jakarta' });
    const fmtClock = new Intl.DateTimeFormat('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: 'Asia/Jakarta' });
    const fmtDate = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Jakarta' });

    // Semua teks dari server disisipkan lewat textContent (bukan innerHTML), supaya nama tamu tidak bisa menyisipkan HTML.
    function el(tag, attrs = {}, ...children) {
        const node = document.createElement(tag);
        for (const [key, value] of Object.entries(attrs)) {
            if (key === 'class') node.className = value;
            else if (key === 'text') node.textContent = value;
            else if (key.startsWith('on')) node.addEventListener(key.slice(2), value);
            else if (value !== false && value != null) node.setAttribute(key, value === true ? '' : value);
        }
        for (const child of children.flat()) if (child != null) node.append(child);
        return node;
    }

    const button = (label, style, onclick) => el('button', { type: 'button', class: `btn btn-sm btn-${style}`, text: label, onclick });

    const store = {
        get(key) { try { return localStorage.getItem(key); } catch { return null; } },
        set(key, value) { try { localStorage.setItem(key, value); } catch { /* abaikan */ } },
    };

        const toast = (message, type) => window.MieTemu.toast(message, type);

    async function api(path, { method = 'GET', body } = {}) {
        const res = await fetch(API + path, {
            method,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body ? JSON.stringify(body) : undefined,
        });

        if (res.status === 401) {
            window.location.href = '/login';
            throw new Error('Sesi berakhir. Silakan masuk lagi.');
        }
        if (res.status === 419) {
            setTimeout(() => window.location.reload(), 1200);
            throw new Error('Halaman kedaluwarsa, memuat ulang...');
        }

        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            let message = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Terjadi kesalahan.');
            if (res.status === 403) message = 'Anda tidak memiliki izin untuk tindakan ini.';
            else if (res.status >= 500) message = 'Terjadi kesalahan di server. Coba lagi.';
            else if (res.status === 404 && /No query results/i.test(message)) message = 'Data tidak ditemukan.';
            const error = new Error(message);
            error.status = res.status;
            throw error;
        }
        return data;
    }

    const confirmBox = (message, okLabel) => window.MieTemu.confirm(message, okLabel);

    async function guarded(fn) {
        if (state.busy) return;
        state.busy = true;
        document.body.classList.add('busy');
        try {
            await fn();
        } catch (err) {
            toast(err.message, 'err');
        } finally {
            state.busy = false;
            document.body.classList.remove('busy');
        }
    }

    const canManage = () => state.branch?.roles.some((r) => r === 'admin' || r === 'host');
    const isAdmin = () => Boolean(state.branch?.roles.includes('admin'));

    // ---------- Antrean: render ----------
    function renderChips() {
        $('chips').replaceChildren(...Object.keys(STATUS).map((key) => el('button', {
            class: 'chip' + (key === state.status ? ' active' : ''),
            type: 'button',
            role: 'tab',
            'aria-selected': String(key === state.status),
            onclick: () => {
                state.status = key;
                state.page = 1;
                renderChips();
                loadQueue();
            },
        },
        el('span', { class: 'n', text: String(state.summary[key] ?? 0) }),
        el('span', { class: 'l', text: STATUS[key] }))));
    }

    function metaLine(ticket) {
        const parts = [`${ticket.party_size} orang`, `Masuk ${fmtTime.format(new Date(ticket.created_at))}`];
        const extra = {
            called: ['Dipanggil', ticket.called_at],
            seated: ['Duduk', ticket.seated_at],
            completed: ['Selesai', ticket.completed_at],
            cancelled: ['Dibatalkan', ticket.cancelled_at],
            no_show: ['Dipanggil', ticket.called_at],
        }[ticket.status];
        if (extra && extra[1]) parts.push(`${extra[0]} ${fmtTime.format(new Date(extra[1]))}`);
        if (ticket.status === 'seated' && ticket.table_name) parts.push(`Meja ${ticket.table_name}`);
        if (ticket.source === 'walk_in') parts.push('Walk-in');
        return parts.map((text) => el('span', { text }));
    }

    function ticketCard(ticket) {
        const actions = (ACTIONS[ticket.status] || []).map((action) => button(action.label, action.style, () => runAction(ticket, action)));

        return el('article', { class: `ticket st-${ticket.status}` },
            el('div', { class: 'ticket-head' },
                el('span', { class: 'ticket-code', text: ticket.code }),
                el('span', { class: `badge st-${ticket.status}`, text: STATUS[ticket.status] })),
            el('div', { class: 'ticket-name', text: ticket.customer_name }),
            el('div', { class: 'ticket-meta' }, metaLine(ticket)),
            actions.length ? el('div', { class: 'ticket-actions' }, actions) : null);
    }

    function renderQueue() {
        renderChips();

        $('tickets').replaceChildren(...state.tickets.map(ticketCard));
        $('empty').hidden = state.tickets.length > 0;
        $('empty').textContent = `Tidak ada tiket dengan status "${STATUS[state.status]}".`;
        $('btn-more').hidden = state.page >= state.lastPage;

        $('page-title').textContent = state.branch.name;
        if (state.sessionDate) {
            $('session-date').textContent = fmtDate.format(new Date(`${state.sessionDate}T12:00:00+07:00`));
        }
    }

    // ---------- Antrean: data ----------
    async function loadQueue({ append = false, silent = false } = {}) {
        if (!state.branch || !canManage()) return;

        const branchId = state.branch.id;
        const status = state.status;
        const page = append ? state.page + 1 : 1;

        try {
            const res = await api(`/branches/${branchId}/queue?status=${status}&page=${page}`);
            if (branchId !== state.branch.id || status !== state.status) return; // pengguna sudah pindah

            state.tickets = append ? state.tickets.concat(res.data) : res.data;
            state.page = res.meta.current_page;
            state.lastPage = res.meta.last_page;
            state.summary = res.summary;
            state.sessionDate = res.session_date;
            $('updated-at').textContent = `Diperbarui ${fmtClock.format(new Date())} WIB`;
            renderQueue();
        } catch (err) {
            if (silent) $('updated-at').textContent = 'Gagal memperbarui, mencoba lagi...';
            else toast(err.message, 'err');
        }
    }

    async function runAction(ticket, action) {
        if (action.custom === 'seat') return openSeatDialog(ticket);
        if (action.confirm && !(await confirmBox(action.confirm(ticket)))) return;

        await guarded(async () => {
            await api(`/queue-tickets/${ticket.id}/${action.key}`, { method: 'POST' });
            toast(`${ticket.code} ${action.done}.`, 'ok');
            await loadQueue();
        });
    }

    // ---------- Pilih meja saat "Dudukkan" ----------
    async function openSeatDialog(ticket) {
        const dlg = $('seat-dialog');
        $('seat-title').textContent = `Dudukkan ${ticket.code}`;
        $('seat-sub').textContent = `${ticket.customer_name} · ${ticket.party_size} orang. Pilih meja:`;
        $('seat-options').replaceChildren();
        $('seat-empty').hidden = true;
        dlg.showModal();

        try {
            const res = await api(`/branches/${state.branch.id}/tables`);
            const fits = res.data
                .filter((t) => t.status === 'available' && t.capacity >= ticket.party_size)
                .sort((a, b) => a.capacity - b.capacity || a.name.localeCompare(b.name, 'id', { numeric: true }));

            if (!fits.length) {
                $('seat-empty').textContent = `Tidak ada meja kosong untuk ${ticket.party_size} orang saat ini.`;
                $('seat-empty').hidden = false;
                return;
            }

            $('seat-options').replaceChildren(...fits.map((table) => el('button', {
                type: 'button',
                class: 'btn btn-ghost table-pick',
                onclick: () => chooseTable(ticket, table),
            },
            el('strong', { text: table.name }),
            el('span', { text: `${table.capacity} kursi` }))));
        } catch (err) {
            $('seat-empty').textContent = err.message;
            $('seat-empty').hidden = false;
        }
    }

    async function chooseTable(ticket, table) {
        $('seat-dialog').close();
        await guarded(async () => {
            await api(`/queue-tickets/${ticket.id}/seat`, { method: 'POST', body: { dining_table_id: table.id } });
            toast(`${ticket.code} duduk di meja ${table.name}.`, 'ok');
            await loadQueue();
        });
    }

    $('seat-cancel').addEventListener('click', () => $('seat-dialog').close());

    // ---------- Meja ----------
    function tableCard(table) {
        const admin = isAdmin();
        const actions = [];

        if (table.status === 'needs_cleaning') {
            actions.push(button('Tandai bersih', 'primary', () => cleanTable(table)));
        }
        if (admin) {
            actions.push(button('Ubah', 'ghost', () => openTableDialog(table)));
            if (table.status === 'inactive') {
                actions.push(button('Aktifkan', 'ghost', () => toggleTable(table, true)));
            } else if (table.status !== 'occupied' && table.status !== 'allocated') {
                actions.push(button('Nonaktifkan', 'danger', () => toggleTable(table, false)));
            }
        }

        const current = table.current;

        return el('article', { class: `tcard ts-${table.status}` },
            el('div', { class: 'ticket-head' },
                el('span', { class: 'ticket-code', text: table.name }),
                el('span', { class: `badge ts-${table.status}`, text: TABLE_STATUS[table.status] })),
            el('div', { class: 'ticket-meta' }, el('span', { text: `${table.capacity} kursi` })),
            current ? el('div', { class: 'tcard-current' },
                el('strong', { text: `${current.ticket_code ?? ''} ${current.customer_name}`.trim() }),
                el('span', { text: `${current.party_size} orang · sejak ${fmtTime.format(new Date(current.seated_at))}` })) : null,
            actions.length ? el('div', { class: 'ticket-actions' }, actions) : null);
    }

    function renderTables() {
        $('tables-title').textContent = `Meja · ${state.branch.name}`;
        $('btn-add-table').hidden = !isAdmin();

        $('t-summary').replaceChildren(...Object.keys(TABLE_STATUS).map((key) => el('span', { class: `pill ts-${key}` },
            el('strong', { text: String(state.tableSummary[key] ?? 0) }),
            ` ${TABLE_STATUS[key]}`)));

        $('tables-grid').replaceChildren(...state.tables.map(tableCard));
        $('tables-empty').hidden = state.tables.length > 0;
    }

    async function loadTables({ silent = false } = {}) {
        if (!state.branch || !canManage()) return;

        const branchId = state.branch.id;

        try {
            const res = await api(`/branches/${branchId}/tables`);
            if (branchId !== state.branch.id) return;

            state.tables = res.data;
            state.tableSummary = res.summary;
            $('tables-updated').textContent = `Diperbarui ${fmtClock.format(new Date())} WIB`;
            renderTables();
        } catch (err) {
            if (silent) $('tables-updated').textContent = 'Gagal memperbarui, mencoba lagi...';
            else toast(err.message, 'err');
        }
    }

    const cleanTable = (table) => guarded(async () => {
        await api(`/tables/${table.id}/clean`, { method: 'POST' });
        toast(`Meja ${table.name} siap dipakai.`, 'ok');
        await loadTables();
    });

    async function toggleTable(table, active) {
        if (!active && !(await confirmBox(`Nonaktifkan meja ${table.name}? Meja tidak akan bisa dipakai sampai diaktifkan lagi.`))) return;

        await guarded(async () => {
            await api(`/tables/${table.id}`, { method: 'PATCH', body: { is_active: active } });
            toast(`Meja ${table.name} ${active ? 'diaktifkan' : 'dinonaktifkan'}.`, 'ok');
            await loadTables();
        });
    }

    function openTableDialog(table = null) {
        state.editingTable = table;
        $('table-dialog-title').textContent = table ? `Ubah meja ${table.name}` : 'Tambah meja';
        $('table-name').value = table ? table.name : '';
        $('table-capacity').value = table ? table.capacity : 4;
        $('table-error').hidden = true;
        $('table-dialog').showModal();
    }

    $('btn-add-table').addEventListener('click', () => openTableDialog());
    $('table-cancel').addEventListener('click', () => $('table-dialog').close());
    $('btn-refresh-tables').addEventListener('click', () => loadTables());

    $('table-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const error = $('table-error');
        const submit = $('table-submit');
        const name = $('table-name').value.trim();
        const capacity = parseInt($('table-capacity').value, 10);

        if (!name || !Number.isInteger(capacity) || capacity < 1 || capacity > 50) {
            error.textContent = 'Isi nama meja dan kapasitas antara 1 dan 50.';
            error.hidden = false;
            return;
        }

        submit.disabled = true;
        try {
            if (state.editingTable) {
                await api(`/tables/${state.editingTable.id}`, { method: 'PATCH', body: { name, capacity } });
                toast(`Meja ${name} diperbarui.`, 'ok');
            } else {
                await api(`/branches/${state.branch.id}/tables`, { method: 'POST', body: { name, capacity } });
                toast(`Meja ${name} ditambahkan.`, 'ok');
            }
            $('table-dialog').close();
            await loadTables();
        } catch (err) {
            error.textContent = err.message;
            error.hidden = false;
        } finally {
            submit.disabled = false;
        }
    });

    // ---------- Tab dan cabang ----------
    function setView(view) {
        state.view = view;
        const queue = view === 'queue';

        $('queue-view').hidden = !queue;
        $('tables-view').hidden = queue;
        $('tab-queue').classList.toggle('active', queue);
        $('tab-tables').classList.toggle('active', !queue);
        $('tab-queue').setAttribute('aria-selected', String(queue));
        $('tab-tables').setAttribute('aria-selected', String(!queue));

        if (queue) loadQueue();
        else loadTables();
    }

    $('tab-queue').addEventListener('click', () => setView('queue'));
    $('tab-tables').addEventListener('click', () => setView('tables'));

    function selectBranch(branch) {
        state.branch = branch;
        state.status = 'waiting';
        state.page = 1;
        state.tickets = [];
        state.summary = {};
        state.tables = [];
        store.set('mietemu.branch', String(branch.id));

        const manage = canManage();
        $('tabs').hidden = !manage;
        $('queue-view').hidden = true;
        $('tables-view').hidden = true;
        $('notice').hidden = manage;

        if (!manage) {
            const roles = branch.roles.map((r) => ROLE[r] ?? r).join(', ');
            $('notice').textContent = `Peran Anda di cabang ini: ${roles}. Antrean dan meja hanya dikelola oleh Admin dan Host. Halaman pesanan dapur akan tersedia di tahap berikutnya.`;
            return;
        }

        $('page-title').textContent = branch.name;
        setView(state.view);
    }

    // ---------- Event antrean ----------
    $('btn-call-next').addEventListener('click', () => guarded(async () => {
        const res = await api(`/branches/${state.branch.id}/queue/call-next`, { method: 'POST' });
        toast(`${res.data.code} (${res.data.customer_name}) dipanggil.`, 'ok');
        await loadQueue();
    }));

    $('btn-refresh').addEventListener('click', () => loadQueue());
    $('btn-more').addEventListener('click', () => loadQueue({ append: true }));

    $('btn-walkin').addEventListener('click', () => {
        $('walkin-form').reset();
        $('walkin-error').hidden = true;
        $('walkin-dialog').showModal();
    });
    $('walkin-cancel').addEventListener('click', () => $('walkin-dialog').close());

    $('walkin-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const error = $('walkin-error');
        const submit = $('walkin-submit');
        const size = parseInt($('walkin-size').value, 10);

        if (!Number.isInteger(size) || size < 1 || size > 20) {
            error.textContent = 'Jumlah orang harus antara 1 dan 20.';
            error.hidden = false;
            return;
        }

        submit.disabled = true;
        try {
            const res = await api(`/branches/${state.branch.id}/queue-tickets`, {
                method: 'POST',
                body: { party_size: size, guest_name: $('walkin-name').value.trim() || null },
            });
            $('walkin-dialog').close();
            toast(`Tiket ${res.data.code} dibuat.`, 'ok');
            state.status = 'waiting';
            await loadQueue();
        } catch (err) {
            error.textContent = err.message;
            error.hidden = false;
        } finally {
            submit.disabled = false;
        }
    });

    function refreshCurrentView() {
        if (state.view === 'queue') loadQueue({ silent: true });
        else loadTables({ silent: true });
    }

    setInterval(() => {
        if (!document.hidden && !state.busy && !document.querySelector('dialog[open]')) refreshCurrentView();
    }, POLL_MS);

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && !state.busy) refreshCurrentView();
    });

    // ---------- Mulai ----------
    async function init() {
        try {
            state.branches = (await api('/branches')).data;
        } catch (err) {
            $('notice').hidden = false;
            $('notice').textContent = err.message;
            return;
        }

        if (!state.branches.length) {
            $('notice').hidden = false;
            $('notice').textContent = 'Akun Anda belum terhubung ke cabang mana pun.';
            return;
        }

        const select = $('branch-select');
        select.replaceChildren(...state.branches.map((b) => el('option', { value: b.id, text: b.name })));
        select.disabled = state.branches.length === 1;

        const saved = store.get('mietemu.branch');
        const initial = state.branches.find((b) => String(b.id) === saved) ?? state.branches[0];
        select.value = String(initial.id);

        select.addEventListener('change', () => {
            const branch = state.branches.find((b) => String(b.id) === select.value);
            if (branch) selectBranch(branch);
        });

        const roles = [...new Set(state.branches.flatMap((b) => b.roles))].map((r) => ROLE[r] ?? r).join(', ');
               $('who').append(el('small', { text: roles }));
        selectBranch(initial);
    }

    init();
})();