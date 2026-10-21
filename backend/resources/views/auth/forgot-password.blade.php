<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lupa Password - MieTemu</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>

<body class="auth">
    <main class="card">
        <div class="icon-wrap">
            <svg class="i" viewBox="0 0 24 24" aria-hidden="true">
                <rect x="3" y="5" width="18" height="14" rx="2" />
                <path d="m3 7 9 6 9-6" />
            </svg>
        </div>
        <h1>Lupa Password</h1>
        <p class="muted">Masukkan email akun Anda. Kami akan mengirim tautan untuk membuat password baru.</p>

        <form data-ajax data-action="{{ url('/api/v1/auth/forgot-password') }}">
            <div class="field">
                <label for="email">Email</label>
                <div class="input-wrap">
                    <input id="email" name="email" type="email" required autofocus autocomplete="username"
                        placeholder="nama@email.com">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block submit-btn">
                <span class="spinner"></span><span class="btn-text">Kirim Tautan Reset</span>
            </button>
            <p class="msg" data-msg role="status"></p>
        </form>

        <p class="footer-note"><a href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
    </main>
    <script src="{{ asset('js/auth.js') }}?v={{ filemtime(public_path('js/auth.js')) }}" defer></script>
</body>

</html>
