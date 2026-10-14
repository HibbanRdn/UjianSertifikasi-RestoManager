<header class="ops-header content-width"><div><h1>Kunjungan aktif</h1><p>Kosongkan Meja menutup kunjungan dan melepas meja secara atomik. Data pesanan tetap tersimpan.</p></div><span><strong><?= count($visits) ?></strong> aktif</span></header>
<section class="content-width ops-section">
    <?php if ($visits === []): ?>
        <div class="empty-state"><h2>Belum ada kunjungan aktif.</h2><p>Meja yang tersedia siap dipilih pada checkout pelanggan berikutnya.</p></div>
    <?php else: ?>
        <div class="visit-grid">
            <?php foreach ($visits as $visit): ?>
                <article class="visit-card">
                    <header><div><span><?= e($visit['visit_code']) ?></span><h2>Meja <?= e($visit['table_code']) ?></h2><p><?= e($visit['customer_name_snapshot']) ?></p></div><strong><?= (int) $visit['order_count'] ?> order</strong></header>
                    <div class="visit-orders"><?php foreach ($visit['orders'] as $order): ?><div><span><?= e($order['order_code']) ?></span><strong class="status-<?= e($order['status']) ?>"><?= $order['status'] === 'served' ? 'Disajikan' : 'Diterima' ?></strong></div><?php endforeach; ?></div>
                    <?php if ((bool) $visit['can_close']): ?>
                        <form action="/staff/visits/<?= (int) $visit['id'] ?>/close" method="post" data-submit-label="Menutup…" data-confirm="Tutup kunjungan dan kosongkan meja <?= e($visit['table_code']) ?>?"><input type="hidden" name="_token" value="<?= e($csrfToken) ?>"><button class="btn btn-primary w-100" type="submit">Kosongkan Meja</button></form>
                    <?php else: ?>
                        <button class="btn btn-light w-100" type="button" disabled>Masih ada order belum disajikan</button>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
