<section class="page-header compact-header"><div class="container narrow-copy"><h1>Riwayat</h1><p>Pesanan yang sudah disajikan tetap tersimpan, termasuk dari kunjungan lama.</p></div></section>
<section class="section-space pt-0"><div class="container">
    <?php if ($isGuest): ?>
        <div class="empty-state"><h2>Masuk untuk melihat Riwayat.</h2><p>Riwayat akun lain tidak pernah ditampilkan kepada guest.</p><a class="btn btn-leaf" href="/login?return_to=/history">Masuk</a></div>
    <?php elseif ($orders === []): ?>
        <div class="empty-state"><h2>Belum ada riwayat pesanan.</h2><p>Pesanan akan berpindah ke sini setelah Petugas menekan Sajikan.</p><a class="btn btn-outline-leaf" href="/menu">Lihat menu</a></div>
    <?php else: ?>
        <div class="order-list">
            <?php foreach ($orders as $order): ?>
                <article><div><span><?= e($order['order_code']) ?></span><h2>Meja <?= e($order['table_code']) ?></h2><p>Disajikan <?= e($order['served_at']) ?> WIB</p></div><div class="order-actions"><strong><?= e(rupiah((int) $order['total_idr'])) ?></strong><a href="/orders/<?= (int) $order['id'] ?>/receipt">Lihat bukti</a></div></article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div></section>
