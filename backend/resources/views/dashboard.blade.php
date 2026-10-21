<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Antrean - MieTemu</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>

<body class="dash" data-user="{{ $user->name }}">

    <header class="topbar">
        <div class="topbar-inner">
            <div class="brand">
                <span class="brand-mark">
                    <svg class="i" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 11h18a9 9 0 0 1-18 0z" />
                        <path d="M8 7c0-1.5 1-1.5 1-3M12 7c0-1.5 1-1.5 1-3M16 7c0-1.5 1-1.5 1-3" />
                    </svg>
                </span>
                MieTemu
            </div>

            <div class="topbar-actions">
                <span class="who" id="who"></span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-light btn-sm">Keluar</button>
                </form>
            </div>

            <label class="branch-select">
                <span class="sr-only">Pilih cabang</span>
                <select id="branch-select" aria-label="Pilih cabang"></select>
            </label>
        </div>
    </header>

    <main class="wrap">
        <section id="notice" class="notice" hidden></section>

        <div id="queue-view" hidden>
            <div class="toolbar">
                <div>
                    <h1 class="page-title" id="page-title">Antrean</h1>
                    <p class="muted-sm"><span id="session-date"></span> &middot; <span id="updated-at"></span></p>
                </div>
                <div class="toolbar-actions">
                    <button type="button" id="btn-call-next" class="btn btn-primary">Panggil berikutnya</button>
                    <button type="button" id="btn-walkin" class="btn btn-ghost">+ Walk-in</button>
                    <button type="button" id="btn-refresh" class="btn btn-ghost" aria-label="Muat ulang">
                        <svg class="i" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M21 12a9 9 0 1 1-3-6.7" />
                            <path d="M21 3v6h-6" />
                        </svg>
                    </button>
                </div>
            </div>

            <div id="chips" class="chips" role="tablist" aria-label="Filter status"></div>

            <div id="tickets" class="tickets" aria-live="polite"></div>
            <p id="empty" class="empty" hidden></p>
            <div class="more"><button type="button" id="btn-more" class="btn btn-ghost" hidden>Muat lebih
                    banyak</button></div>
        </div>
    </main>

    <dialog id="confirm-dialog" aria-labelledby="confirm-title">
        <form method="dialog" class="dlg">
            <h2 class="dlg-title" id="confirm-title">Konfirmasi</h2>
            <p class="dlg-text" id="confirm-message"></p>
            <div class="dlg-actions">
                <button class="btn btn-ghost" value="cancel">Batal</button>
                <button class="btn btn-primary" id="confirm-ok" value="ok">Ya, lanjutkan</button>
            </div>
        </form>
    </dialog>

    <dialog id="walkin-dialog" aria-labelledby="walkin-title">
        <form id="walkin-form" class="dlg" novalidate>
            <h2 class="dlg-title" id="walkin-title">Tiket walk-in</h2>
            <div class="field">
                <label for="walkin-size">Jumlah orang</label>
                <div class="input-wrap"><input id="walkin-size" type="number" inputmode="numeric" min="1"
                        max="20" value="2" required></div>
            </div>
            <div class="field">
                <label for="walkin-name">Nama (opsional)</label>
                <div class="input-wrap"><input id="walkin-name" type="text" maxlength="100" autocomplete="off"
                        placeholder="Contoh: Pak Budi"></div>
            </div>
            <p class="msg err" id="walkin-error" hidden></p>
            <div class="dlg-actions">
                <button type="button" class="btn btn-ghost" id="walkin-cancel">Batal</button>
                <button type="submit" class="btn btn-primary" id="walkin-submit">Buat tiket</button>
            </div>
        </form>
    </dialog>

    <div class="toasts" id="toasts" aria-live="polite"></div>

    <script src="{{ asset('js/dashboard.js') }}?v={{ filemtime(public_path('js/dashboard.js')) }}" defer></script>
</body>

</html>
