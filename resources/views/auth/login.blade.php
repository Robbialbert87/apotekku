<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk · {{ config('apotek.name') }}</title>
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('hms-theme') === 'dark' ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
<div class="auth-card">
    <div class="auth-brand">
        <div class="auth-brand-mark">A</div>
        <strong>{{ config('apotek.name') }}</strong>
    </div>

    <div style="text-align:center; margin-bottom:var(--space-6);">
        <h1 style="font-size:var(--fs-h3); margin-bottom:6px;">Selamat datang kembali</h1>
        <p class="text-muted text-small" style="margin:0;">Masuk untuk mengelola invoice dan pembayaran supplier.</p>
    </div>

    @if (session('status'))
        <div class="alert-custom alert-success-custom" role="status" style="margin-bottom:var(--space-4);">
            <i class="bi bi-check-circle-fill"></i><div>{{ session('status') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert-custom alert-danger-custom" role="alert" style="margin-bottom:var(--space-4);">
            <i class="bi bi-exclamation-triangle-fill"></i><div>{{ $errors->first() }}</div>
        </div>
    @endif

    <form action="{{ route('login.store') }}" method="POST">
        @csrf

        <div style="margin-bottom:14px;">
            <label class="form-label-custom" for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control-custom @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" placeholder="nama@apotek.com" required autofocus autocomplete="username">
        </div>

        <div style="margin-bottom:18px;">
            <label class="form-label-custom" for="password">Kata Sandi</label>
            <input type="password" name="password" id="password" class="form-control-custom @error('password') is-invalid @enderror"
                   placeholder="••••••••" required autocomplete="current-password">
        </div>

        <div class="cluster" style="justify-content:space-between; margin-bottom:18px;">
            <label class="form-check-custom">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                <span class="text-small">Ingat saya</span>
            </label>
        </div>

        <button type="submit" class="btn-custom btn-primary-custom w-100" style="justify-content:center;">
            <i class="bi bi-box-arrow-in-right"></i> Masuk
        </button>
    </form>

    <p class="auth-note">Akses hanya untuk pengguna yang terdaftar dan aktif.</p>
</div>
</body>
</html>
