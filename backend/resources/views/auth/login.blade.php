<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk - MieTemu</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>

<body class="auth">
    <main class="card">
        <div class="icon-wrap">
            <svg class="i" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
        </div>
        <h1>Masuk Staf</h1>
        <p class="muted">Dashboard antrean <strong>MieTemu</strong></p>

        <form data-ajax data-action="{{ url('/login') }}">
            <div class="field">
                <label for="email">Email</label>
                <div class="input-wrap">
                    <input id="email" name="email" type="email" required autofocus autocomplete="username"
                        placeholder="Masukkan Email Anda">
                </div>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <input id="password" name="password" type="password" class="has-toggle" required
                        autocomplete="current-password" placeholder="Masukkan Password Anda">
                    <button type="button" class="toggle-vis" data-toggle-password="password"
                        aria-label="Tampilkan password">
                        <svg class="i" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block submit-btn">
                <span class="spinner"></span><span class="btn-text">Masuk</span>
            </button>
            <p class="msg" data-msg role="status"></p>
        </form>

        <p class="footer-note"><a href="{{ route('password.request') }}">Lupa password?</a></p>
    </main>
    <script src="{{ asset('js/auth.js') }}?v={{ filemtime(public_path('js/auth.js')) }}" defer></script>
</body>

</html>
