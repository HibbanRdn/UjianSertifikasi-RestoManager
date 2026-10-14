<header class="catalog-header content-width">
    <div><h1>Menu RestoManager</h1><p>Pilih hidangan yang tersedia. Harga ditampilkan dalam rupiah dan bersumber dari database.</p></div>
    <form class="catalog-search" action="/menu" method="get" role="search">
        <?php if ($selectedCategory !== null): ?><input type="hidden" name="category" value="<?= (int) $selectedCategory ?>"><?php endif; ?>
        <svg aria-hidden="true"><use href="#icon-search"/></svg>
        <label class="visually-hidden" for="catalog-search">Cari dalam menu</label>
        <input id="catalog-search" name="q" type="search" maxlength="80" placeholder="Cari hidangan…" value="<?= e($searchQuery) ?>">
        <button type="submit">Cari</button>
    </form>
</header>

<section class="content-width catalog-section">
    <nav class="category-filter" aria-label="Filter kategori">
        <a class="<?= $selectedCategory === null ? 'active' : '' ?>" href="/menu<?= $searchQuery !== '' ? '?q=' . rawurlencode($searchQuery) : '' ?>">Semua</a>
        <?php foreach ($categories as $category): ?>
            <a class="<?= $selectedCategory === (int) $category['id'] ? 'active' : '' ?>" href="/menu?category=<?= (int) $category['id'] ?><?= $searchQuery !== '' ? '&amp;q=' . rawurlencode($searchQuery) : '' ?>"><?= e($category['name']) ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($searchQuery !== ''): ?>
        <div class="result-summary"><strong><?= count($menuItems) ?></strong> hasil untuk “<?= e($searchQuery) ?>”<?php if ($selectedCategory !== null): ?> pada kategori terpilih<?php endif; ?>. <a href="/menu">Hapus pencarian</a></div>
    <?php endif; ?>

    <?php if ($menuItems === []): ?>
        <div class="empty-state">
            <span class="empty-illustration"><svg><use href="#icon-search"/></svg></span>
            <h2>Menu belum ditemukan</h2>
            <p>Coba kata kunci lain atau tampilkan semua kategori.</p>
            <a class="btn btn-primary" href="/menu">Tampilkan semua menu</a>
        </div>
    <?php else: ?>
        <div class="menu-grid">
            <?php foreach ($menuItems as $item): ?>
                <?php require dirname(__DIR__) . '/components/menu-card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
