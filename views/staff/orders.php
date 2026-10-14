<header class="ops-header content-width"><div><h1>Pesanan masuk</h1><p>Urutan paling lama ditampilkan lebih dulu. Aksi Sajikan hanya mengubah satu order dan tidak melepaskan meja.</p></div><span><strong><?= count($orders) ?></strong> menunggu</span></header>
<section class="content-width ops-section">
    <?php if ($orders === []): ?>
        <div class="empty-state"><span class="empty-illustration"><svg><use href="#icon-receipt"/></svg></span><h2>Tidak ada pesanan menunggu.</h2><p>Pesanan baru akan muncul setelah checkout pelanggan berhasil.</p><a class="btn btn-outline-leaf" href="/staff/visits">Lihat kunjungan</a></div>
    <?php else: ?>
        <div class="ops-order-list">
            <?php foreach ($orders as $order): ?>
                <article class="ops-order-card">
                    <div class="ops-order-main"><span><?= e($order['order_code']) ?> · <?= e(date('H:i', strtotime((string) $order['created_at']))) ?> WIB</span><h2>Meja <?= e($order['table_code']) ?></h2><p><?= e($order['customer_name_snapshot']) ?></p></div>
                    <ul><?php foreach ($order['items'] as $item): ?><li><strong><?= (int) $item['quantity'] ?>×</strong> <?= e($item['menu_name_snapshot']) ?></li><?php endforeach; ?></ul>
                    <div class="ops-order-payment"><span><?= $order['method'] === 'qris_sim' ? 'QRIS' : 'Tunai' ?> · Lunas simulasi</span><strong><?= e(rupiah((int) $order['total_idr'])) ?></strong></div>
                    <form action="/staff/orders/<?= (int) $order['id'] ?>/serve" method="post" data-submit-label="Menyajikan…" data-confirm="Tandai pesanan <?= e($order['order_code']) ?> sudah disajikan?"><input type="hidden" name="_token" value="<?= e($csrfToken) ?>"><button class="btn btn-primary" type="submit">Sajikan</button></form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
