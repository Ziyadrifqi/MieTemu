<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Password - MieTemu</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>

<body class="auth">
    <main class="card">
        <div class="icon-wrap">
            <svg class="i" viewBox="0 0 24 24" aria-hidden="true">
                <rect x="4" y="11" width="16" height="10" rx="2" />
                <path d="M8 11V7a4 4 0 0 1 8 0v4" />
            </svg>
        </div>
        <h1>Reset Password</h1>
        <p class="muted">Buat password baru untuk akun <strong>{{ $email }}</strong></p>

        <form data-ajax data-hide-on-success data-action="{{ url('/api/v1/auth/reset-password') }}">
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="field">
                <label for="password">Password baru</label>
                <div class="input-wrap">
                    <input id="password" name="password" type="password" class="has-toggle" minlength="8" required
                        autofocus autocomplete="new-password" placeholder="Minimal 8 karakter">
                    <button type="button" class="toggle-vis" data-toggle-password="password"
                        aria-label="Tampilkan password">
                        <svg class="i" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="field">
                <label for="password_confirmation">Ulangi password baru</label>
                <div class="input-wrap">
                    <input id="password_confirmation" name="password_confirmation" type="password" class="has-toggle"
                        minlength="8" required autocomplete="new-password" placeholder="Ketik ulang password">
                    <button type="button" class="toggle-vis" data-toggle-password="password_confirmation"
                        aria-label="Tampilkan password">
                        <svg class="i" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block submit-btn">
                <span class="spinner"></span><span class="btn-text">Simpan Password</span>
            </button>
            <p class="msg" data-msg role="status"></p>
        </form>

        <p class="footer-note"><a href="{{ route('login') }}">Ke halaman masuk</a></p>
    </main>
    <script src="{{ asset('js/auth.js') }}?v={{ filemtime(public_path('js/auth.js')) }}" defer></script>
</body>

</html>
