<article class="menu-card">
    <div class="menu-image-wrap">
        <img src="<?= e($item['image_path'] ?: '/assets/img/menu-placeholder.svg') ?>" alt="<?= e($item['name']) ?>" width="900" height="900" loading="lazy">
        <span class="availability <?= (int) $item['is_available'] === 1 ? 'is-available' : 'is-unavailable' ?>"><?= (int) $item['is_available'] === 1 ? 'Tersedia' : 'Habis' ?></span>
    </div>
    <div class="menu-card-body">
        <span class="menu-category"><?= e($item['category_name']) ?></span>
        <h2><?= e($item['name']) ?></h2>
        <p><?= e($item['description'] ?: 'Deskripsi menu belum tersedia.') ?></p>
        <div class="menu-card-footer">
            <strong><?= e(rupiah((int) $item['price_idr'])) ?></strong>
            <?php if (($authUser['role'] ?? 'customer') === 'customer'): ?>
                <form action="/cart/items" method="post" data-submit-label="Menambah…">
                    <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="menu_item_id" value="<?= (int) $item['id'] ?>">
                    <button type="submit" <?= (int) $item['is_available'] !== 1 ? 'disabled' : '' ?>><?= $authUser === null ? 'Tambah' : 'Tambah ke keranjang' ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</article>
