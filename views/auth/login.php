<section class="auth-section">
    <div class="container auth-grid">
        <div class="auth-context">
            <h1>Kembali ke meja dan pesanan Anda.</h1>
            <p>Satu halaman masuk digunakan untuk Customer, Petugas, dan Admin. Sistem mengarahkan setiap role ke area yang tepat.</p>
            <div class="auth-ticket">
                <span>Akses terpisah</span>
                <strong>Customer · Petugas · Admin</strong>
            </div>
        </div>
        <div class="auth-panel">
            <h2>Masuk</h2>
            <p class="text-secondary">Gunakan email dan password akun Anda.</p>
            <?php if ($loginError !== null): ?>
                <div class="alert alert-danger" role="alert"><?= e($loginError) ?></div>
            <?php endif; ?>
            <form action="/login" method="post" novalidate>
                <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="email" maxlength="190" autocomplete="email" value="<?= e($old['email'] ?? '') ?>" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" id="password" name="password" type="password" maxlength="72" autocomplete="current-password" required>
                </div>
                <button class="btn btn-leaf btn-lg w-100" type="submit">Masuk ke RestoManager</button>
            </form>
            <p class="auth-switch">Belum punya akun pelanggan? <a href="/register<?= $returnTo !== '' ? '?return_to=' . rawurlencode($returnTo) : '' ?>">Daftar sekarang</a>.</p>
        </div>
    </div>
</section>
