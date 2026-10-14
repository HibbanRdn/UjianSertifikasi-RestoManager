<header class="checkout-header content-width"><span>Langkah 1 dari 3</span><h1>Ringkasan Pesanan</h1><p>Pastikan meja, item, jumlah, dan harga sudah sesuai sebelum memilih pembayaran.</p></header>
<section class="content-width checkout-layout">
    <div class="checkout-sheet">
        <div class="checkout-identity"><div><span>Pelanggan</span><strong><?= e($customer['name']) ?></strong></div><div><span>Meja</span><strong><?= e($table['table_code']) ?></strong><small><?= $visit !== null ? 'Kunjungan aktif' : 'Belum diklaim hingga checkout berhasil' ?></small></div></div>
        <div class="checkout-items">
            <?php foreach ($cart['items'] as $item): ?>
                <article><img src="<?= e($item['image_path']) ?>" alt="" width="120" height="120"><div><h2><?= e($item['name']) ?></h2><p><?= (int) $item['quantity'] ?> × <?= e(rupiah((int) $item['price_idr'])) ?></p></div><strong><?= e(rupiah((int) $item['line_total_idr'])) ?></strong></article>
            <?php endforeach; ?>
        </div>
        <div class="checkout-total"><span>Total bayar</span><strong><?= e(rupiah((int) $cart['total_idr'])) ?></strong></div>
    </div>
    <aside class="checkout-side">
        <div class="estimate-box"><svg><use href="#icon-history"/></svg><span><strong><?= (int) $estimate_min ?>–<?= (int) $estimate_max ?> menit</strong><small>Perkiraan waktu, bukan jaminan real-time.</small></span></div>
        <div class="review-notice"><strong>Harga diperiksa server.</strong><p>Jika harga atau ketersediaan berubah setelah halaman ini dibuka, transaksi dihentikan dan Anda diminta meninjau ulang. Keranjang tetap tersimpan.</p></div>
        <a class="btn btn-primary w-100" href="/checkout/payment">Selanjutnya</a><a class="btn btn-light w-100 mt-2" href="/cart">Kembali ke keranjang</a>
    </aside>
</section>
