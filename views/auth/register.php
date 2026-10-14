<section class="auth-section">
    <div class="container auth-grid">
        <div class="auth-context">
            <h1>Satu akun untuk setiap kunjungan.</h1>
            <p>Data akun membantu RestoManager memastikan pesanan, meja, dan riwayat hanya terlihat oleh pemiliknya.</p>
            <ul>
                <li>Katalog tetap bisa dilihat tanpa akun.</li>
                <li>Meja dipilih saat mulai memesan.</li>
                <li>Password disimpan dalam bentuk hash.</li>
            </ul>
        </div>
        <div class="auth-panel">
            <h2>Daftar pelanggan</h2>
            <p class="text-secondary">Isi empat data utama. Role akun ditetapkan otomatis sebagai Customer.</p>
            <form action="/register" method="post" novalidate>
                <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">

                <div class="mb-3">
                    <label class="form-label" for="name">Nama lengkap</label>
                    <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" type="text" maxlength="100" autocomplete="name" value="<?= e($old['name'] ?? '') ?>" required autofocus>
                    <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" type="email" maxlength="190" autocomplete="email" value="<?= e($old['email'] ?? '') ?>" required>
                    <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="phone">Nomor telepon</label>
                    <input class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="phone" name="phone" type="tel" maxlength="20" autocomplete="tel" value="<?= e($old['phone'] ?? '') ?>" required>
                    <div class="form-text">Gunakan 8–20 digit; tanda + di awal diperbolehkan.</div>
                    <?php if (isset($errors['phone'])): ?><div class="invalid-feedback"><?= e($errors['phone']) ?></div><?php endif; ?>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                        <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirmation">Ulangi password</label>
                        <input class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>" id="password_confirmation" name="password_confirmation" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                        <?php if (isset($errors['password_confirmation'])): ?><div class="invalid-feedback"><?= e($errors['password_confirmation']) ?></div><?php endif; ?>
                    </div>
                </div>
                <button class="btn btn-leaf btn-lg w-100 mt-4" type="submit">Buat akun Customer</button>
            </form>
            <p class="auth-switch">Sudah punya akun? <a href="/login<?= $returnTo !== '' ? '?return_to=' . rawurlencode($returnTo) : '' ?>">Masuk di sini</a>.</p>
        </div>
    </div>
</section>
