<header class="table-select-hero content-width"><div><h1>Pilih meja Anda</h1><p>Pilihan ini hanya disimpan sementara. Meja baru berubah menjadi terpakai setelah checkout pertama berhasil.</p></div><span><strong><?= count($tables) ?></strong> meja tersedia</span></header>
<section class="content-width table-select-section">
    <?php if ($tables === []): ?>
        <div class="empty-state"><h2>Belum ada meja tersedia.</h2><p>Silakan tunggu atau tanyakan kepada Petugas. Pilihan menu Anda tetap tersimpan.</p><a class="btn btn-outline-leaf" href="/menu">Kembali ke menu</a></div>
    <?php else: ?>
        <form action="/tables/select" method="post" data-submit-label="Memilih meja…">
            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
            <fieldset><legend class="visually-hidden">Meja yang tersedia</legend>
                <div class="table-grid">
                    <?php foreach ($tables as $table): ?>
                        <label class="table-option">
                            <input type="radio" name="table_id" value="<?= (int) $table['id'] ?>" <?= $selectedTableId === (int) $table['id'] ? 'checked' : '' ?> required>
                            <span class="table-visual"><i></i><i></i><i></i><i></i><b><?= e($table['table_code']) ?></b></span>
                            <span>Tersedia</span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            <div class="table-select-actions"><a class="btn btn-light" href="/menu">Kembali ke menu</a><button class="btn btn-primary" type="submit"><?= $hasPending ? 'Pilih dan tambahkan menu' : 'Gunakan meja ini' ?></button></div>
        </form>
    <?php endif; ?>
</section>
