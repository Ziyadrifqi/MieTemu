<x-layouts.app title="Restoran">
    <div class="toolbar">
        <div>
            <h1 class="page-title">Restoran</h1>
            <p class="muted-sm">Semua restoran yang terdaftar di MieTemu.</p>
        </div>
    </div>

    <section class="panel">
        <h2 class="panel-title">Tambah restoran</h2>
        <form method="POST" action="{{ route('super.businesses.store') }}" class="inline-form" novalidate>
            @csrf
            <div class="field grow">
                <label for="name">Nama restoran</label>
                <div class="input-wrap">
                    <input id="name" name="name" type="text" maxlength="120" value="{{ old('name') }}"
                        placeholder="Contoh: Mie Temu Group" required>
                </div>
                @error('name')
                    <p class="msg err">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary">Tambah</button>
        </form>
    </section>

    <section class="panel">
        @if ($businesses->isEmpty())
            <p class="empty">Belum ada restoran. Tambahkan yang pertama di atas.</p>
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Cabang</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($businesses as $business)
                            <tr>
                                <td data-label="Nama"><strong>{{ $business->name }}</strong></td>
                                <td data-label="Cabang">{{ $business->branches_count }}</td>
                                <td data-label="Status">
                                    <x-ui.status-badge :active="$business->status === 'active'" off="Ditangguhkan" />
                                </td>
                                <td class="cell-actions">
                                    <a class="btn btn-ghost btn-sm"
                                        href="{{ route('super.businesses.show', $business) }}">Kelola</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.app>
