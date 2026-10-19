<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - MieTemu</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #c0392b 0%, #8e2a20 50%, #4a1812 100%);
            margin: 0;
            display: grid;
            place-items: center;
            min-height: 100vh;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        /* Ornamen lingkaran dekoratif di background */
        body::before,
        body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
            pointer-events: none;
        }

        body::before {
            width: 380px;
            height: 380px;
            top: -120px;
            right: -120px;
        }

        body::after {
            width: 260px;
            height: 260px;
            bottom: -80px;
            left: -80px;
        }

        .card {
            position: relative;
            background: #fff;
            padding: 2.5rem 2rem 2rem;
            border-radius: 20px;
            width: min(440px, 94vw);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(0, 0, 0, 0.02);
            animation: cardIn 0.5s cubic-bezier(0.22, 1, 0.36, 1);
            z-index: 1;
        }

        @keyframes cardIn {
            from {
                opacity: 0;
                transform: translateY(24px) scale(0.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Ikon gembok di atas card */
        .icon-wrap {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: linear-gradient(135deg, #e74c3c, #a93226);
            display: grid;
            place-items: center;
            margin: -3.2rem auto 1.2rem;
            box-shadow: 0 8px 20px rgba(192, 57, 43, 0.45);
        }

        .icon-wrap svg {
            width: 28px;
            height: 28px;
            stroke: #fff;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        h1 {
            margin: 0 0 0.4rem;
            font-size: 1.5rem;
            text-align: center;
            color: #2c1a17;
            letter-spacing: -0.02em;
        }

        .muted {
            color: #7a6a66;
            font-size: 0.9rem;
            text-align: center;
            margin: 0 0 0.5rem;
            line-height: 1.5;
        }

        .muted strong {
            color: #c0392b;
            font-weight: 600;
            word-break: break-all;
        }

        .field {
            margin-top: 1.1rem;
        }

        label {
            display: block;
            margin: 0 0 0.4rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: #4a3a36;
        }

        .input-wrap {
            position: relative;
        }

        input {
            width: 100%;
            padding: 0.8rem 2.7rem 0.8rem 0.9rem;
            border: 1.5px solid #e2d9d5;
            border-radius: 12px;
            font-size: 0.95rem;
            font-family: inherit;
            background: #faf8f7;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
            outline: none;
        }

        input:focus {
            border-color: #c0392b;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(192, 57, 43, 0.12);
        }

        input:invalid:not(:placeholder-shown) {
            border-color: #e74c3c;
        }

        /* Tombol mata untuk toggle password */
        .toggle-vis {
            position: absolute;
            right: 0.55rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: 0;
            padding: 0.35rem;
            cursor: pointer;
            color: #9a8a86;
            display: grid;
            place-items: center;
            border-radius: 6px;
            transition: color 0.15s, background 0.15s;
        }

        .toggle-vis:hover {
            color: #c0392b;
            background: #f3ebe9;
        }

        .toggle-vis svg {
            width: 19px;
            height: 19px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        /* Indikator kekuatan password */
        .strength {
            display: flex;
            gap: 4px;
            margin-top: 0.5rem;
        }

        .strength span {
            flex: 1;
            height: 4px;
            border-radius: 2px;
            background: #eee4e1;
            transition: background 0.3s;
        }

        .strength-label {
            font-size: 0.75rem;
            color: #9a8a86;
            margin-top: 0.35rem;
            min-height: 1em;
        }

        .hint {
            font-size: 0.78rem;
            color: #9a8a86;
            margin-top: 0.35rem;
        }

        button[type="submit"] {
            margin-top: 1.5rem;
            width: 100%;
            padding: 0.9rem;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #e74c3c, #a93226);
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            letter-spacing: 0.01em;
            box-shadow: 0 8px 20px rgba(192, 57, 43, 0.35);
            transition: transform 0.15s, box-shadow 0.2s, opacity 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        button[type="submit"]:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 26px rgba(192, 57, 43, 0.45);
        }

        button[type="submit"]:active:not(:disabled) {
            transform: translateY(0);
        }

        button[type="submit"]:disabled {
            opacity: 0.65;
            cursor: not-allowed;
            box-shadow: none;
        }

        /* Spinner saat loading */
        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .loading .spinner {
            display: inline-block;
        }

        .loading .btn-text {
            opacity: 0.85;
        }

        #msg {
            margin-top: 1rem;
            font-size: 0.9rem;
            text-align: center;
            line-height: 1.5;
            border-radius: 10px;
            padding: 0;
            transition: all 0.3s;
        }

        #msg:not(:empty) {
            padding: 0.7rem 0.9rem;
        }

        #msg.ok {
            color: #1e8449;
            background: #eafaf1;
            border: 1px solid #b8e6cd;
        }

        #msg.err {
            color: #c0392b;
            background: #fdeeec;
            border: 1px solid #f5c6c0;
        }

        #msg.info {
            color: #7a6a66;
            background: #faf8f7;
            border: 1px solid #eee4e1;
        }

        /* Footer kecil */
        .footer-note {
            text-align: center;
            font-size: 0.78rem;
            color: #9a8a86;
            margin-top: 1.4rem;
        }

        .footer-note a {
            color: #c0392b;
            text-decoration: none;
            font-weight: 600;
        }

        .footer-note a:hover {
            text-decoration: underline;
        }

        /* Success state */
        .success-check {
            display: none;
            text-align: center;
            padding: 1rem 0;
            animation: cardIn 0.4s ease;
        }

        .success-check .circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #eafaf1;
            display: grid;
            place-items: center;
            margin: 0 auto 1rem;
            animation: pop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .success-check .circle svg {
            width: 36px;
            height: 36px;
            stroke: #1e8449;
            fill: none;
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-dasharray: 40;
            stroke-dashoffset: 40;
            animation: draw 0.5s 0.2s forwards;
        }

        @keyframes pop {
            from {
                transform: scale(0);
            }

            to {
                transform: scale(1);
            }
        }

        @keyframes draw {
            to {
                stroke-dashoffset: 0;
            }
        }

        .success-check h2 {
            font-size: 1.2rem;
            color: #1e8449;
            margin: 0 0 0.4rem;
        }

        .success-check p {
            color: #7a6a66;
            font-size: 0.9rem;
            margin: 0;
        }
    </style>
</head>

<body>
    <main class="card">
        <div class="icon-wrap">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <rect x="4" y="11" width="16" height="10" rx="2"></rect>
                <path d="M8 11V7a4 4 0 0 1 8 0v4"></path>
                <circle cx="12" cy="16" r="1.2"></circle>
            </svg>
        </div>

        <h1>Reset Password</h1>
        <p class="muted">Masukkan password baru untuk akun <strong>{{ $email }}</strong>.</p>

        <form id="reset-form" data-token="{{ $token }}" data-email="{{ $email }}" novalidate>
            <div class="field">
                <label for="password">Password baru</label>
                <div class="input-wrap">
                    <input id="password" name="password" type="password" minlength="8" required
                        placeholder="Minimal 8 karakter" autocomplete="new-password">
                    <button type="button" class="toggle-vis" data-target="password" aria-label="Tampilkan password">
                        <svg viewBox="0 0 24 24">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </button>
                </div>
                <div class="strength" id="strength" aria-hidden="true">
                    <span></span><span></span><span></span><span></span>
                </div>
                <div class="strength-label" id="strength-label"></div>
            </div>

            <div class="field">
                <label for="password_confirmation">Ulangi password baru</label>
                <div class="input-wrap">
                    <input id="password_confirmation" name="password_confirmation" type="password" minlength="8"
                        required placeholder="Ketik ulang password" autocomplete="new-password">
                    <button type="button" class="toggle-vis" data-target="password_confirmation"
                        aria-label="Tampilkan password">
                        <svg viewBox="0 0 24 24">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </button>
                </div>
                <div class="hint" id="match-hint"></div>
            </div>

            <button type="submit" id="submit-btn">
                <span class="spinner"></span>
                <span class="btn-text">Simpan Password</span>
            </button>
        </form>

        <div class="success-check" id="success-check">
            <div class="circle">
                <svg viewBox="0 0 24 24">
                    <path d="M20 6L9 17l-5-5" />
                </svg>
            </div>
            <h2>Password berhasil diubah!</h2>
            <p>Silakan login kembali dengan password baru Anda.</p>
        </div>

        <p id="msg" role="status" aria-live="polite"></p>

        <p class="footer-note">Ingat password Anda? <a href="/login">Kembali ke Login</a></p>
    </main>

    <script>
        const form = document.getElementById('reset-form');
        const msg = document.getElementById('msg');
        const btn = document.getElementById('submit-btn');
        const btnText = btn.querySelector('.btn-text');
        const pw = document.getElementById('password');
        const pwc = document.getElementById('password_confirmation');
        const strengthBars = document.querySelectorAll('#strength span');
        const strengthLabel = document.getElementById('strength-label');
        const matchHint = document.getElementById('match-hint');
        const successCheck = document.getElementById('success-check');

        /* Toggle show/hide password */
        document.querySelectorAll('.toggle-vis').forEach(btnEye => {
            btnEye.addEventListener('click', () => {
                const input = document.getElementById(btnEye.dataset.target);
                const isPw = input.type === 'password';
                input.type = isPw ? 'text' : 'password';
                btnEye.setAttribute('aria-label', isPw ? 'Sembunyikan password' : 'Tampilkan password');
                btnEye.innerHTML = isPw ?
                    '<svg viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>' :
                    '<svg viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>';
            });
        });

        /* Indikator kekuatan password */
        function scorePassword(val) {
            let score = 0;
            if (val.length >= 8) score++;
            if (val.length >= 12) score++;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
            if (/\d/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;
            return Math.min(score, 4);
        }

        const strengthColors = ['#e74c3c', '#e67e22', '#f1c40f', '#2ecc71'];
        const strengthTexts = ['Sangat lemah', 'Lemah', 'Cukup', 'Kuat', 'Sangat kuat'];

        pw.addEventListener('input', () => {
            const val = pw.value;
            const score = scorePassword(val);
            strengthBars.forEach((bar, i) => {
                bar.style.background = val && i < score ? strengthColors[score - 1] : '#eee4e1';
            });
            strengthLabel.textContent = val ? strengthTexts[score] : '';
            checkMatch();
        });

        function checkMatch() {
            if (!pwc.value) {
                matchHint.textContent = '';
                matchHint.style.color = '';
                return;
            }
            if (pw.value === pwc.value) {
                matchHint.textContent = '✓ Password cocok';
                matchHint.style.color = '#1e8449';
            } else {
                matchHint.textContent = '✗ Password tidak cocok';
                matchHint.style.color = '#c0392b';
            }
        }
        pwc.addEventListener('input', checkMatch);

        /* Submit */
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Validasi klien sederhana
            if (pw.value.length < 8) {
                showMsg('err', 'Password minimal 8 karakter.');
                pw.focus();
                return;
            }
            if (pw.value !== pwc.value) {
                showMsg('err', 'Konfirmasi password tidak cocok.');
                pwc.focus();
                return;
            }

            setLoading(true);
            showMsg('info', 'Memproses...');

            try {
                const res = await fetch('/api/v1/auth/reset-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        token: form.dataset.token,
                        email: form.dataset.email,
                        password: pw.value,
                        password_confirmation: pwc.value,
                    }),
                });
                const data = await res.json().catch(() => ({}));

                if (res.ok) {
                    msg.className = '';
                    msg.textContent = '';
                    form.hidden = true;
                    successCheck.style.display = 'block';
                } else {
                    showMsg('err', data.errors ?
                        Object.values(data.errors).flat().join(' ') :
                        (data.message || 'Terjadi kesalahan.'));
                    setLoading(false);
                }
            } catch (err) {
                showMsg('err', 'Tidak bisa terhubung ke server.');
                setLoading(false);
            }
        });

        function setLoading(on) {
            btn.disabled = on;
            btn.classList.toggle('loading', on);
            btnText.textContent = on ? 'Menyimpan...' : 'Simpan Password';
        }

        function showMsg(type, text) {
            msg.className = type;
            msg.textContent = text;
        }
    </script>
</body>

</html>
