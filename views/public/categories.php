<header class="page-intro content-width">
    <div><h1>Pilih kategori,<br>temukan favorit.</h1><p>Setiap hitungan berasal dari menu aktif yang terlihat di database.</p></div>
    <img src="/assets/img/brand/food-composition.webp" alt="Pilihan hidangan RestoManager" width="1200" height="900">
</header>

<section class="content-width content-section">
    <?php $categoryImages = ['Makanan' => '/assets/img/menu/ayam-bakar-madu.webp', 'Minuman' => '/assets/img/menu/es-teh-manis.webp', 'Camilan' => '/assets/img/menu/kentang-goreng.webp']; ?>
    <div class="category-gallery">
        <?php foreach ($categories as $index => $category): ?>
            <a class="category-showcase category-showcase-<?= ($index % 3) + 1 ?>" href="/menu?category=<?= (int) $category['id'] ?>">
                <div><span><?= (int) $category['menu_count'] ?> menu tersedia</span><h2><?= e($category['name']) ?></h2><p>Buka kategori dan lihat harga serta ketersediaan terkini.</p><strong>Jelajahi <svg><use href="#icon-arrow"/></svg></strong></div>
                <img src="<?= e($categoryImages[$category['name']] ?? '/assets/img/menu-placeholder.svg') ?>" alt="Pilihan <?= e(strtolower((string) $category['name'])) ?>" width="600" height="600" loading="lazy">
            </a>
        <?php endforeach; ?>
    </div>
</section>
