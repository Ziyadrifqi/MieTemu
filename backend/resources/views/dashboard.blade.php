<x-layouts.app title="Dashboard Antrean" :branch-select="true">
    <section id="notice" class="notice" hidden></section>

    <nav id="tabs" class="tabs" role="tablist" aria-label="Menu" hidden>
        <button type="button" class="tab active" id="tab-queue" role="tab" aria-selected="true">Antrean</button>
        <button type="button" class="tab" id="tab-tables" role="tab" aria-selected="false">Meja</button>
    </nav>

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

    <div id="tables-view" hidden>
        <div class="toolbar">
            <div>
                <h1 class="page-title" id="tables-title">Meja</h1>
                <p class="muted-sm"><span id="tables-updated"></span></p>
            </div>
            <div class="toolbar-actions">
                <button type="button" id="btn-add-table" class="btn btn-primary" hidden>+ Meja</button>
                <button type="button" id="btn-refresh-tables" class="btn btn-ghost" aria-label="Muat ulang">
                    <svg class="i" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M21 12a9 9 0 1 1-3-6.7" />
                        <path d="M21 3v6h-6" />
                    </svg>
                </button>
            </div>
        </div>

        <div id="t-summary" class="t-summary"></div>
        <div id="tables-grid" class="tables-grid" aria-live="polite"></div>
        <p id="tables-empty" class="empty" hidden>Belum ada meja di cabang ini.</p>
    </div>

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

    <dialog id="seat-dialog" aria-labelledby="seat-title">
        <div class="dlg">
            <h2 class="dlg-title" id="seat-title">Dudukkan</h2>
            <p class="dlg-text" id="seat-sub"></p>
            <div id="seat-options" class="table-picks"></div>
            <p class="msg err" id="seat-empty" hidden></p>
            <div class="dlg-actions">
                <button type="button" class="btn btn-ghost" id="seat-cancel">Batal</button>
            </div>
        </div>
    </dialog>

    <dialog id="table-dialog" aria-labelledby="table-dialog-title">
        <form id="table-form" class="dlg" novalidate>
            <h2 class="dlg-title" id="table-dialog-title">Meja</h2>
            <div class="field">
                <label for="table-name">Nama meja</label>
                <div class="input-wrap"><input id="table-name" type="text" maxlength="50" autocomplete="off"
                        placeholder="Contoh: T7" required></div>
            </div>
            <div class="field">
                <label for="table-capacity">Kapasitas (kursi)</label>
                <div class="input-wrap"><input id="table-capacity" type="number" inputmode="numeric"
                        min="1" max="50" value="4" required></div>
            </div>
            <p class="msg err" id="table-error" hidden></p>
            <div class="dlg-actions">
                <button type="button" class="btn btn-ghost" id="table-cancel">Batal</button>
                <button type="submit" class="btn btn-primary" id="table-submit">Simpan</button>
            </div>
        </form>
    </dialog>

    @push('scripts')
        <script src="@assetv('js/dashboard.js')" defer></script>
    @endpush
</x-layouts.app>
