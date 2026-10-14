<section class="receipt-page content-width">
    <div class="receipt-success"><span><svg><use href="#icon-receipt"/></svg></span><div><h1>Pesanan berhasil dibuat.</h1><p>Data berikut diambil dari transaksi yang sudah tersimpan di database.</p></div></div>
    <article class="receipt-paper">
        <header><div><span>RestoManager</span><strong><?= e($receipt['order_code']) ?></strong></div><div><span>Meja</span><strong><?= e($receipt['table_code']) ?></strong></div></header>
        <div class="receipt-meta"><div><span>Pelanggan</span><strong><?= e($receipt['customer_name_snapshot']) ?></strong></div><div><span>Waktu</span><strong><?= e(date('d M Y, H:i', strtotime((string) $receipt['created_at']))) ?> WIB</strong></div><div><span>Pembayaran</span><strong><?= $receipt['method'] === 'qris_sim' ? 'QRIS — Simulasi' : 'Tunai — Simulasi' ?></strong></div><div><span>Status</span><strong>Lunas (Simulasi)</strong></div></div>
        <div class="receipt-items">
            <?php foreach ($receipt['items'] as $item): ?><div><span><strong><?= e($item['menu_name_snapshot']) ?></strong><small><?= (int) $item['quantity'] ?> × <?= e(rupiah((int) $item['price_idr_snapshot'])) ?></small></span><strong><?= e(rupiah((int) $item['line_total_idr'])) ?></strong></div><?php endforeach; ?>
        </div>
        <div class="receipt-total"><span>Total</span><strong><?= e(rupiah((int) $receipt['total_idr'])) ?></strong></div>
        <div class="receipt-estimate">Perkiraan penyajian <?= (int) $receipt['estimated_minutes_min'] ?>–<?= (int) $receipt['estimated_minutes_max'] ?> menit. Bukan jaminan real-time.</div>
        <footer>BUKTI SIMULASI — bukan bukti transfer atau penerimaan uang nyata.</footer>
    </article>
    <div class="receipt-actions"><a class="btn btn-primary" href="/orders">Lihat Orders</a><?php if ($receipt['visit_status'] === 'active'): ?><a class="btn btn-outline-leaf" href="/menu">Tambah pesanan</a><?php endif; ?></div>
</section>
