@props(['branchSelect' => false])

<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="{{ route('dashboard') }}">
            <span class="brand-mark">
                <svg class="i" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 11h18a9 9 0 0 1-18 0z" />
                    <path d="M8 7c0-1.5 1-1.5 1-3M12 7c0-1.5 1-1.5 1-3M16 7c0-1.5 1-1.5 1-3" />
                </svg>
            </span>
            {{ config('app.name') }}
        </a>

        <div class="topbar-actions">
            <span class="who" id="who">{{ Auth::user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}" id="logout-form">
                @csrf
                <input type="hidden" name="reason" id="logout-reason" value="">
                <button type="submit" class="btn btn-light btn-sm">Keluar</button>
            </form>
        </div>

        @if ($branchSelect)
            <label class="branch-select">
                <span class="sr-only">Pilih cabang</span>
                <select id="branch-select" aria-label="Pilih cabang"></select>
            </label>
        @endif
    </div>
</header>
