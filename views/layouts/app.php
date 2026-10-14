<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <meta name="theme-color" content="#fffaf6">
    <title><?= e($pageTitle ?? 'RestoManager') ?> — RestoManager</title>
    <link rel="stylesheet" href="/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="app-body">
<svg class="icon-sprite" aria-hidden="true">
    <symbol id="icon-home" viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5v8a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></symbol>
    <symbol id="icon-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></symbol>
    <symbol id="icon-menu" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h10"/></symbol>
    <symbol id="icon-cart" viewBox="0 0 24 24"><path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 8H7"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></symbol>
    <symbol id="icon-receipt" viewBox="0 0 24 24"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6M9 16h3"/></symbol>
    <symbol id="icon-history" viewBox="0 0 24 24"><path d="M4 5v5h5M5.5 9A8 8 0 1 1 4 15M12 8v5l3 2"/></symbol>
    <symbol id="icon-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></symbol>
    <symbol id="icon-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></symbol>
    <symbol id="icon-dashboard" viewBox="0 0 24 24"><path d="M4 13h6V4H4zm10 7h6v-9h-6zM4 20h6v-3H4zm10-13h6V4h-6z"/></symbol>
    <symbol id="icon-arrow" viewBox="0 0 24 24"><path d="M5 12h14M14 7l5 5-5 5"/></symbol>
</svg>

<?php
$isInternal = in_array($authUser['role'] ?? null, ['staff', 'admin'], true);
$isActive = static fn (string $path): string => $currentPath === $path ? ' is-active' : '';
?>

<a class="skip-link" href="#konten-utama">Lewati ke konten utama</a>
<aside class="app-sidebar" aria-label="Navigasi utama">
    <a class="brand-lockup" href="/" aria-label="RestoManager, kembali ke beranda">
        <span class="brand-symbol" aria-hidden="true"><span></span><span></span></span><strong>RestoManager</strong>
    </a>
    <nav class="sidebar-nav">
        <?php if (!$isInternal): ?>
            <a class="sidebar-link<?= $isActive('/') ?>" href="/"><svg><use href="#icon-home"/></svg><span>Beranda</span></a>
            <a class="sidebar-link<?= $isActive('/menu') ?>" href="/menu"><svg><use href="#icon-menu"/></svg><span>Menu</span></a>
            <a class="sidebar-link<?= $isActive('/categories') ?>" href="/categories"><svg><use href="#icon-grid"/></svg><span>Kategori</span></a>
            <a class="sidebar-link<?= $isActive('/cart') ?>" href="/cart"><svg><use href="#icon-cart"/></svg><span>Keranjang</span></a>
            <a class="sidebar-link<?= $isActive('/orders') ?>" href="/orders"><svg><use href="#icon-receipt"/></svg><span>Orders</span></a>
            <a class="sidebar-link<?= $isActive('/history') ?>" href="/history"><svg><use href="#icon-history"/></svg><span>Riwayat</span></a>
        <?php else: ?>
            <a class="sidebar-link<?= $isActive('/staff/dashboard') ?>" href="/staff/dashboard"><svg><use href="#icon-dashboard"/></svg><span>Operasional</span></a>
            <a class="sidebar-link<?= $isActive('/staff/orders') ?>" href="/staff/orders"><svg><use href="#icon-receipt"/></svg><span>Pesanan</span></a>
            <a class="sidebar-link<?= $isActive('/staff/visits') ?>" href="/staff/visits"><svg><use href="#icon-grid"/></svg><span>Kunjungan</span></a>
            <?php if (($authUser['role'] ?? null) === 'admin'): ?>
                <a class="sidebar-link<?= str_starts_with($currentPath, '/admin') && $currentPath !== '/admin/reports' ? ' is-active' : '' ?>" href="/admin/administration"><svg><use href="#icon-grid"/></svg><span>Administrasi</span></a>
                <a class="sidebar-link<?= $isActive('/admin/reports') ?>" href="/admin/reports"><svg><use href="#icon-history"/></svg><span>Laporan</span></a>
            <?php endif; ?>
            <a class="sidebar-link" href="/menu"><svg><use href="#icon-menu"/></svg><span>Katalog</span></a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-note"><strong>Dine-in</strong><span>QRIS & tunai simulasi</span></div>
</aside>

<div class="app-frame">
    <header class="app-topbar">
        <a class="mobile-brand" href="/" aria-label="RestoManager, kembali ke beranda">
            <span class="brand-symbol" aria-hidden="true"><span></span><span></span></span><strong>RestoManager</strong>
        </a>
        <?php if (!$isInternal): ?>
            <form class="topbar-search" action="/menu" method="get" role="search">
                <svg aria-hidden="true"><use href="#icon-search"/></svg>
                <label class="visually-hidden" for="global-search">Cari menu atau kategori</label>
                <input id="global-search" name="q" type="search" maxlength="80" placeholder="Cari menu atau kategori…" value="<?= e($searchQuery ?? '') ?>">
                <button type="submit" aria-label="Cari"><svg><use href="#icon-arrow"/></svg></button>
            </form>
        <?php else: ?>
            <div class="topbar-title"><span>Ruang kerja</span><strong><?= e(($authUser['role'] ?? '') === 'admin' ? 'Admin' : 'Petugas') ?></strong></div>
        <?php endif; ?>
        <div class="topbar-account">
            <?php if (!$isInternal): ?>
                <a class="topbar-icon-link" href="/cart"><svg><use href="#icon-cart"/></svg><span>Keranjang</span></a>
            <?php endif; ?>
            <?php if ($authUser === null): ?>
                <a class="topbar-icon-link" href="/login"><svg><use href="#icon-user"/></svg><span>Masuk</span></a>
                <a class="btn btn-primary btn-sm topbar-register" href="/register">Daftar</a>
            <?php else: ?>
                <div class="dropdown">
                    <button class="account-chip dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="account-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) $authUser['name'], 0, 1))) ?></span>
                        <span class="account-copy"><strong><?= e($authUser['name']) ?></strong><small><?= e($authUser['role'] === 'staff' ? 'Petugas' : ucfirst((string) $authUser['role'])) ?></small></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if ($authUser['role'] === 'customer'): ?>
                            <li><a class="dropdown-item" href="/profile">Profil saya</a></li>
                        <?php elseif ($authUser['role'] === 'staff'): ?>
                            <li><a class="dropdown-item" href="/staff/dashboard">Operasional</a></li>
                            <li><a class="dropdown-item" href="/staff/orders">Pesanan masuk</a></li>
                            <li><a class="dropdown-item" href="/staff/visits">Kunjungan aktif</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item" href="/admin/dashboard">Dashboard Admin</a></li>
                            <li><a class="dropdown-item" href="/admin/administration">Pusat administrasi</a></li>
                            <li><a class="dropdown-item" href="/admin/categories">Kategori</a></li>
                            <li><a class="dropdown-item" href="/admin/menu">Menu</a></li>
                            <li><a class="dropdown-item" href="/admin/tables">Meja</a></li>
                            <li><a class="dropdown-item" href="/admin/staff">Petugas</a></li>
                            <li><a class="dropdown-item" href="/admin/reports">Laporan</a></li>
                            <li><a class="dropdown-item" href="/staff/dashboard">Operasional</a></li>
                            <li><a class="dropdown-item" href="/staff/orders">Pesanan masuk</a></li>
                            <li><a class="dropdown-item" href="/staff/visits">Kunjungan aktif</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><form action="/logout" method="post" data-submit-label="Keluar…"><input type="hidden" name="_token" value="<?= e($csrfToken) ?>"><button class="dropdown-item text-danger" type="submit">Keluar</button></form></li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!empty($flashSuccess) || !empty($flashError)): ?>
        <div class="content-width flash-stack" aria-live="polite">
            <?php if (!empty($flashSuccess)): ?><div class="alert alert-success" role="status"><?= e($flashSuccess) ?></div><?php endif; ?>
            <?php if (!empty($flashError)): ?><div class="alert alert-danger" role="alert"><?= e($flashError) ?></div><?php endif; ?>
        </div>
    <?php endif; ?>

    <main id="konten-utama"><?= $content ?></main>

    <footer class="site-footer">
        <div><a class="brand-lockup footer-brand" href="/"><span class="brand-symbol" aria-hidden="true"><span></span><span></span></span><strong>RestoManager</strong></a><p>Pemesanan walk-in yang sederhana, aman, dan tercatat.</p></div>
        <div class="footer-links"><a href="/menu">Menu</a><a href="/categories">Kategori</a><a href="/orders">Orders</a></div>
        <p class="simulation-note"><strong>Mode demonstrasi.</strong><br>QRIS dan tunai tidak memindahkan dana nyata.</p>
    </footer>
</div>

<?php if (!$isInternal): ?>
    <nav class="mobile-bottom-nav" aria-label="Navigasi mobile">
        <a class="<?= $isActive('/') ?>" href="/"><svg><use href="#icon-home"/></svg><span>Beranda</span></a>
        <a class="<?= $isActive('/menu') ?>" href="/menu"><svg><use href="#icon-menu"/></svg><span>Menu</span></a>
        <a class="<?= $isActive('/cart') ?>" href="/cart"><svg><use href="#icon-cart"/></svg><span>Keranjang</span></a>
        <a class="<?= $isActive('/orders') ?>" href="/orders"><svg><use href="#icon-receipt"/></svg><span>Orders</span></a>
        <a class="<?= $isActive('/profile') ?>" href="<?= $authUser === null ? '/login' : '/profile' ?>"><svg><use href="#icon-user"/></svg><span>Akun</span></a>
    </nav>
<?php endif; ?>

<script src="/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
