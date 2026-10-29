@php
    use App\Http\Requests\Super\BranchRequest;
    $hasOld = session()->hasOldInput();
    $acceptsQueue = $hasOld ? (bool) old('accepts_queue') : $branch->accepts_queue;
@endphp

<x-layouts.app :title="$branch->name">
    <p class="crumb"><a href="{{ route('super.businesses.show', $branch->business) }}">&larr;
            {{ $branch->business->name }}</a></p>

    <div class="toolbar">
        <div>
            <h1 class="page-title">{{ $branch->name }}</h1>
            <p class="muted-sm"><x-ui.status-badge :active="$branch->is_active" /></p>
        </div>
        <div class="toolbar-actions">
            @if ($branch->is_active)
                <form method="POST" action="{{ route('super.branches.status', $branch) }}"
                    data-confirm="Nonaktifkan cabang {{ $branch->name }}? Staf dan pengunjung tidak bisa lagi memakainya. Riwayat antrean tetap tersimpan."
                    data-confirm-ok="Ya, nonaktifkan">
                    @csrf @method('PATCH')
                    <input type="hidden" name="active" value="0">
                    <button type="submit" class="btn btn-danger">Nonaktifkan</button>
                </form>
            @else
                <form method="POST" action="{{ route('super.branches.status', $branch) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="active" value="1">
                    <button type="submit" class="btn btn-primary">Aktifkan</button>
                </form>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('super.branches.update', $branch) }}" novalidate>
        @csrf @method('PUT')

        @if ($errors->any())
            <div class="msg err panel" role="alert">
                <ul class="error-list">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="panel">
            <h2 class="panel-title">Informasi cabang</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="name">Nama cabang</label>
                    <div class="input-wrap"><input id="name" name="name" type="text" maxlength="120"
                            value="{{ old('name', $branch->name) }}" required></div>
                </div>
                <div class="field">
                    <label for="phone">Telepon</label>
                    <div class="input-wrap"><input id="phone" name="phone" type="tel" maxlength="30"
                            value="{{ old('phone', $branch->phone) }}"></div>
                </div>
                <div class="field span-2">
                    <label for="address">Alamat</label>
                    <div class="input-wrap"><input id="address" name="address" type="text" maxlength="255"
                            value="{{ old('address', $branch->address) }}"></div>
                </div>
                <label class="check span-2">
                    <input type="hidden" name="accepts_queue" value="0">
                    <input type="checkbox" name="accepts_queue" value="1" @checked($acceptsQueue)>
                    <span>Cabang menerima antrean baru</span>
                </label>
            </div>
        </section>

        <section class="panel">
            <h2 class="panel-title">Jam operasional (WIB)</h2>
            <div class="hours">
                @foreach (BranchRequest::DAYS as $day => $label)
                    @php
                        $row = $hours->get($day);
                        $closed = $hasOld ? (bool) old("hours.$day.is_closed") : (bool) $row?->is_closed;
                        $open = old("hours.$day.open_time", $row?->open_time ? substr($row->open_time, 0, 5) : '10:00');
                        $close = old(
                            "hours.$day.close_time",
                            $row?->close_time ? substr($row->close_time, 0, 5) : '21:00',
                        );
                    @endphp
                    <div class="hours-row">
                        <span class="hours-day">{{ $label }}</span>
                        <input type="time" name="hours[{{ $day }}][open_time]" value="{{ $open }}"
                            aria-label="Jam buka {{ $label }}">
                        <span aria-hidden="true">&ndash;</span>
                        <input type="time" name="hours[{{ $day }}][close_time]"
                            value="{{ $close }}" aria-label="Jam tutup {{ $label }}">
                        <label class="check">
                            <input type="checkbox" name="hours[{{ $day }}][is_closed]" value="1"
                                @checked($closed)>
                            <span>Libur</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan perubahan</button>
        </div>
    </form>
</x-layouts.app>
