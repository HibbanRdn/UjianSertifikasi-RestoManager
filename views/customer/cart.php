<header class="page-header"><div class="container narrow-copy"><h1>Keranjang</h1><p>Periksa pilihan menu dan meja sebelum melanjutkan ke ringkasan pesanan.</p></div></header>
<section class="section-space pt-0"><div class="container">
    <?php if ($isGuest): ?>
        <div class="empty-state"><span class="empty-illustration"><svg><use href="#icon-cart"/></svg></span><h2>Belum ada menu dalam keranjang.</h2><p>Masuk terlebih dahulu untuk mulai memesan. Pilihan item dari katalog akan tetap diingat selama proses login.</p><a class="btn btn-primary" href="/login?return_to=/cart">Masuk untuk memesan</a></div>
    <?php elseif ($cart['items'] === []): ?>
        <div class="empty-state"><span class="empty-illustration"><svg><use href="#icon-cart"/></svg></span><h2>Belum ada menu dalam keranjang.</h2><p>Jelajahi katalog dan tambahkan hidangan yang tersedia.</p><a class="btn btn-primary" href="/menu">Lihat menu</a></div>
    <?php else: ?>
        <div class="cart-layout">
            <div class="cart-items">
                <?php foreach ($cart['items'] as $item): ?>
                    <article class="cart-row">
                        <img src="<?= e($item['image_path']) ?>" alt="<?= e($item['name']) ?>" width="180" height="180">
                        <div class="cart-row-copy"><span><?= e($item['category_name']) ?></span><h2><?= e($item['name']) ?></h2><p><?= e(rupiah((int) $item['price_idr'])) ?> / item</p></div>
                        <form class="quantity-form" action="/cart/update" method="post" data-submit-label="Menyimpan…">
                            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="menu_item_id" value="<?= (int) $item['id'] ?>">
                            <label for="qty-<?= (int) $item['id'] ?>">Jumlah</label><input id="qty-<?= (int) $item['id'] ?>" name="quantity" type="number" min="1" max="99" step="1" value="<?= (int) $item['quantity'] ?>"><button type="submit">Perbarui</button>
                        </form>
                        <strong class="cart-line-total"><?= e(rupiah((int) $item['line_total_idr'])) ?></strong>
                        <form action="/cart/remove" method="post" data-submit-label="Menghapus…"><input type="hidden" name="_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="menu_item_id" value="<?= (int) $item['id'] ?>"><button class="cart-remove" type="submit">Hapus</button></form>
                    </article>
                <?php endforeach; ?>
            </div>
            <aside class="cart-summary">
                <h2>Ringkasan</h2>
                <div class="cart-table-status"><span>Meja</span><?php if ($table !== null): ?><strong><?= e($table['table_code']) ?></strong><small><?= $visit !== null ? 'Kunjungan aktif' : 'Pilihan sementara — belum diklaim' ?></small><?php else: ?><strong>Belum dipilih</strong><a href="/tables/select">Pilih meja</a><?php endif; ?></div>
                <div class="cart-total"><span>Total sementara</span><strong><?= e(rupiah((int) $cart['total_idr'])) ?></strong></div>
                <p>Harga dan ketersediaan akan diperiksa ulang pada review checkout.</p>
                <?php if ($table === null): ?><a class="btn btn-primary w-100" href="/tables/select">Pilih meja</a><?php else: ?><a class="btn btn-primary w-100" href="/checkout/review">Tinjau pesanan</a><?php endif; ?>
                <a class="btn btn-outline-leaf w-100 mt-2" href="/menu">Tambah menu lain</a>
                <form class="mt-3" action="/cart/clear" method="post" data-submit-label="Mengosongkan…"><input type="hidden" name="_token" value="<?= e($csrfToken) ?>"><button class="cart-clear" type="submit">Kosongkan keranjang</button></form>
            </aside>
        </div>
    <?php endif; ?>
</div></section>
