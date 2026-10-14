<section class="home-hero content-width">
    <img class="hero-photo" src="/assets/img/brand/hero-banner.webp" alt="Hidangan RestoManager tersaji di meja" width="1600" height="900" fetchpriority="high">
    <div class="hero-content">
        <h1>Makan enak,<br><span>pesan lebih cepat.</span></h1>
        <p>Jelajahi menu, pilih meja yang tersedia, lalu selesaikan pesanan dine-in dari satu alur sederhana.</p>
        <div class="hero-actions">
            <a class="btn btn-primary btn-lg" href="/menu">Lihat menu <svg aria-hidden="true"><use href="#icon-arrow"/></svg></a>
            <?php if ($authUser === null): ?>
                <a class="btn btn-light btn-lg" href="/register">Buat akun</a>
            <?php else: ?>
                <a class="btn btn-light btn-lg" href="<?= e($authUser['role'] === 'customer' ? '/cart' : ($authUser['role'] === 'staff' ? '/staff/dashboard' : '/admin/dashboard')) ?>">Buka area saya</a>
            <?php endif; ?>
        </div>
        <div class="hero-facts" aria-label="Ringkasan alur">
            <span><strong>01</strong>Pilih meja sebelum item pertama</span>
            <span><strong>02</strong>Bayar QRIS atau tunai simulasi</span>
            <span><strong>03</strong>Petugas menyajikan pesanan</span>
        </div>
    </div>
</section>

<section class="trust-strip content-width" aria-label="Keunggulan layanan">
    <div><svg><use href="#icon-menu"/></svg><span><strong>Menu aktual</strong><small>Harga dari database</small></span></div>
    <div><svg><use href="#icon-grid"/></svg><span><strong>Meja dipilih sendiri</strong><small>Diklaim saat checkout sukses</small></span></div>
    <div><svg><use href="#icon-receipt"/></svg><span><strong>Pesanan tercatat</strong><small>Riwayat hanya untuk pemilik</small></span></div>
</section>

<section class="home-section content-width">
    <div class="section-heading">
        <div><h2>Jelajahi kategori</h2><p>Temukan hidangan berdasarkan waktu dan selera Anda.</p></div>
        <a class="section-link" href="/categories">Lihat semua <svg><use href="#icon-arrow"/></svg></a>
    </div>
    <?php $categoryImages = ['Makanan' => '/assets/img/menu/nasi-goreng-kampung.webp', 'Minuman' => '/assets/img/menu/jeruk-peras.webp', 'Camilan' => '/assets/img/menu/pisang-goreng.webp']; ?>
    <div class="category-card-grid">
        <?php foreach ($categories as $category): ?>
            <a class="category-card" href="/menu?category=<?= (int) $category['id'] ?>">
                <span class="category-photo"><img src="<?= e($categoryImages[$category['name']] ?? '/assets/img/menu-placeholder.svg') ?>" alt="" width="400" height="400" loading="lazy"></span>
                <span><strong><?= e($category['name']) ?></strong><small><?= (int) $category['menu_count'] ?> pilihan menu</small></span>
                <svg aria-hidden="true"><use href="#icon-arrow"/></svg>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="home-section content-width">
    <div class="section-heading">
        <div><h2>Menu pilihan hari ini</h2><p>Harga dan ketersediaan ditampilkan langsung dari data menu.</p></div>
        <a class="section-link" href="/menu">Katalog lengkap <svg><use href="#icon-arrow"/></svg></a>
    </div>
    <div class="menu-grid menu-grid-featured">
        <?php foreach ($featuredMenu as $item): ?>
            <?php require dirname(__DIR__) . '/components/menu-card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="walkin-banner content-width">
    <div><h2>Datang, pilih meja, lalu pesan.</h2><p>RestoManager dirancang untuk kunjungan walk-in—tanpa reservasi, tanpa pembayaran nyata, dan tanpa langkah yang disembunyikan.</p></div>
    <img src="/assets/img/brand/food-composition.webp" alt="Aneka hidangan RestoManager" width="1200" height="900">
    <a class="btn btn-dark" href="/menu">Mulai dari menu <svg aria-hidden="true"><use href="#icon-arrow"/></svg></a>
</section>
