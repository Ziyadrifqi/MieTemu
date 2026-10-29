<x-layouts.app :title="$business->name">
    <p class="crumb"><a href="{{ route('super.businesses.index') }}">&larr; Semua restoran</a></p>

    <div class="toolbar">
        <div>
            <h1 class="page-title">{{ $business->name }}</h1>
            <p class="muted-sm">
                <x-ui.status-badge :active="$business->status === 'active'" off="Ditangguhkan" />
            </p>
        </div>
        <div class="toolbar-actions">
            @if ($business->status === 'active')
                <form method="POST" action="{{ route('super.businesses.status', $business) }}"
                    data-confirm="Tangguhkan {{ $business->name }}? Semua staf restoran ini langsung tidak bisa masuk sampai diaktifkan lagi."
                    data-confirm-ok="Ya, tangguhkan">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="suspended">
                    <button type="submit" class="btn btn-danger">Tangguhkan</button>
                </form>
            @else
                <form method="POST" action="{{ route('super.businesses.status', $business) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="btn btn-primary">Aktifkan kembali</button>
                </form>
            @endif
        </div>
    </div>

    <section class="panel">
        <h2 class="panel-title">Nama restoran</h2>
        <form method="POST" action="{{ route('super.businesses.update', $business) }}" class="inline-form" novalidate>
            @csrf @method('PATCH')
            <div class="field grow">
                <label for="b-name" class="sr-only">Nama restoran</label>
                <div class="input-wrap">
                    <input id="b-name" name="name" type="text" maxlength="120"
                        value="{{ old('name', $business->name) }}" required>
                </div>
                @error('name')
                    <p class="msg err">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn btn-ghost">Simpan</button>
        </form>
    </section>

    <section class="panel">
        <h2 class="panel-title">Cabang</h2>

        @if ($business->branches->isEmpty())
            <p class="empty">Belum ada cabang.</p>
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Cabang</th>
                            <th>Alamat</th>
                            <th>Antrean</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($business->branches as $branch)
                            <tr>
                                <td data-label="Cabang"><strong>{{ $branch->name }}</strong></td>
                                <td data-label="Alamat">{{ $branch->address ?: '-' }}</td>
                                <td data-label="Antrean">
                                    <x-ui.status-badge :active="$branch->accepts_queue" on="Dibuka" off="Ditutup" />
                                </td>
                                <td data-label="Status"><x-ui.status-badge :active="$branch->is_active" /></td>
                                <td class="cell-actions">
                                    <a class="btn btn-ghost btn-sm"
                                        href="{{ route('super.branches.edit', $branch) }}">Ubah</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="panel">
        <h2 class="panel-title">Tambah cabang</h2>
        <form method="POST" action="{{ route('super.branches.store', $business) }}" class="form-grid" novalidate>
            @csrf
            <div class="field">
                <label for="n-name">Nama cabang</label>
                <div class="input-wrap"><input id="n-name" name="name" type="text" maxlength="120"
                        value="{{ old('name') }}" required></div>
                @error('name')
                    <p class="msg err">{{ $message }}</p>
                @enderror
            </div>
            <div class="field">
                <label for="n-phone">Telepon (opsional)</label>
                <div class="input-wrap"><input id="n-phone" name="phone" type="tel" maxlength="30"
                        value="{{ old('phone') }}"></div>
                @error('phone')
                    <p class="msg err">{{ $message }}</p>
                @enderror
            </div>
            <div class="field span-2">
                <label for="n-address">Alamat (opsional)</label>
                <div class="input-wrap"><input id="n-address" name="address" type="text" maxlength="255"
                        value="{{ old('address') }}"></div>
            </div>
            <div class="span-2"><button type="submit" class="btn btn-primary">Tambah cabang</button></div>
        </form>
    </section>
</x-layouts.app>
