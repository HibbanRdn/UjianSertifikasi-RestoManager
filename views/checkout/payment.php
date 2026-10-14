<header class="checkout-header content-width"><span>Langkah 2 dari 3</span><h1>Pilih cara pembayaran</h1><p>Pilih satu metode simulasi. Detail QRIS atau tunai baru ditampilkan pada langkah berikutnya.</p></header>
<section class="content-width payment-methods" aria-label="Metode pembayaran simulasi">
    <a class="payment-method" href="/checkout/payment/qris"><span class="payment-method-icon" aria-hidden="true"><svg><use href="#icon-grid"/></svg></span><span><strong>QRIS — Simulasi</strong><small>Tampilkan kode QR dummy dan konfirmasi pembayaran.</small></span><b><?= e(rupiah((int) $cart['total_idr'])) ?></b><svg aria-hidden="true"><use href="#icon-arrow"/></svg></a>
    <a class="payment-method" href="/checkout/payment/cash"><span class="payment-method-icon" aria-hidden="true"><svg><use href="#icon-receipt"/></svg></span><span><strong>Tunai — Simulasi</strong><small>Tampilkan kode dummy untuk alur pembayaran di kasir.</small></span><b><?= e(rupiah((int) $cart['total_idr'])) ?></b><svg aria-hidden="true"><use href="#icon-arrow"/></svg></a>
    <p class="simulation-callout"><strong>Tidak ada transfer dana nyata.</strong> Kedua metode menyimpan status <code>paid_simulated</code> hanya untuk demonstrasi alur aplikasi.</p>
</section>
<div class="content-width payment-back"><a href="/checkout/review">← Kembali ke ringkasan</a></div>
