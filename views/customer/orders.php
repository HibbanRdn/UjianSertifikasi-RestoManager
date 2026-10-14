<section class="page-header compact-header"><div class="container narrow-copy"><h1>Orders</h1><p>Pesanan berstatus diterima akan tampil di sini sampai Petugas menyajikannya.</p></div></section>
<section class="section-space pt-0"><div class="container">
    <?php if ($isGuest): ?>
        <div class="empty-state"><h2>Masuk untuk melihat Orders.</h2><p>Data pesanan hanya ditampilkan kepada pemilik akun.</p><a class="btn btn-leaf" href="/login?return_to=/orders">Masuk</a></div>
    <?php elseif ($orders === []): ?>
        <div class="empty-state"><h2>Belum ada pesanan berlangsung.</h2><p>Pesanan baru akan muncul di sini setelah checkout berhasil.</p><a class="btn btn-outline-leaf" href="/menu">Jelajahi menu</a></div>
    <?php else: ?>
        <div class="order-list">
            <?php foreach ($orders as $order): ?>
                <article><div><span><?= e($order['order_code']) ?></span><h2>Meja <?= e($order['table_code']) ?></h2><p><?= e($order['created_at']) ?> WIB · <?= $order['method'] === 'qris_sim' ? 'QRIS simulasi' : 'Tunai simulasi' ?></p></div><div class="order-actions"><strong><?= e(rupiah((int) $order['total_idr'])) ?></strong><a href="/orders/<?= (int) $order['id'] ?>/receipt">Lihat bukti</a></div></article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div></section>
