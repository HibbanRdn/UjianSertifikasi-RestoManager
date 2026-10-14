<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\SessionStore;
use App\Core\View;
use App\Exceptions\HttpException;
use App\Repositories\DiningTableRepository;
use App\Repositories\MenuItemRepository;
use App\Repositories\VisitRepository;
use App\Services\AuthService;
use App\Services\CartService;
use App\Services\RoleGuard;

final class CustomerCartController
{
    public function __construct(
        private readonly View $view,
        private readonly AuthService $auth,
        private readonly RoleGuard $guard,
        private readonly Csrf $csrf,
        private readonly SessionStore $session,
        private readonly CartService $cart,
        private readonly MenuItemRepository $menuItems,
        private readonly DiningTableRepository $tables,
        private readonly VisitRepository $visits
    ) {
    }

    public function show(Request $request): void
    {
        $this->denyInternalRole();
        $user = $this->auth->user();
        if ($user === null) {
            $this->view->render('customer/cart', ['pageTitle' => 'Keranjang', 'isGuest' => true, 'cart' => ['items' => [], 'total_idr' => 0], 'table' => null, 'visit' => null]);

            return;
        }

        $userId = (int) $user['id'];
        $visit = $this->visits->findActiveByUserId($userId);
        $table = $visit;
        if ($visit === null) {
            $draftTableId = $this->cart->draftTableId($userId);
            $table = $draftTableId !== null ? $this->tables->findActiveById($draftTableId) : null;
            if ($table !== null && $table['status'] !== 'available') {
                $this->cart->clearDraftTable();
                $table = null;
                $this->session->flash('error', 'Meja ini baru saja digunakan pelanggan lain. Pilih meja lain.');
            }
        }

        $this->view->render('customer/cart', [
            'pageTitle' => 'Keranjang',
            'isGuest' => false,
            'cart' => $this->cart->details($userId),
            'table' => $table,
            'visit' => $visit,
        ]);
    }

    public function add(Request $request): never
    {
        $this->assertCsrf($request);
        $this->denyInternalRole();
        $menuItemId = $this->positiveId($request->input('menu_item_id'));
        $item = $this->menuItems->findVisibleById($menuItemId);
        if ($item === null || (int) $item['is_available'] !== 1) {
            $this->session->flash('error', 'Pilihan menu berubah atau tidak tersedia. Periksa kembali menu.');
            Response::redirect('/menu');
        }

        $user = $this->auth->user();
        if ($user === null) {
            $this->cart->rememberPending($menuItemId);
            $this->session->flash('success', 'Pilihan menu disimpan. Masuk atau daftar untuk melanjutkan.');
            Response::redirect('/login?return_to=/menu');
        }

        $userId = (int) $user['id'];
        $visit = $this->visits->findActiveByUserId($userId);
        $draftTableId = $this->cart->draftTableId($userId);
        if ($visit === null && ($draftTableId === null || !$this->tables->isAvailable($draftTableId))) {
            $this->cart->clearDraftTable();
            $this->cart->rememberPending($menuItemId);
            Response::redirect('/tables/select');
        }

        $this->cart->add($userId, $menuItemId);
        $this->session->flash('success', $item['name'] . ' ditambahkan ke keranjang.');
        Response::redirect('/cart');
    }

    public function update(Request $request): never
    {
        $this->assertCsrf($request);
        $this->guard->requireCustomer();
        $userId = (int) $this->auth->user()['id'];
        $this->cart->update($userId, $this->positiveId($request->input('menu_item_id')), $this->validQuantity($request->input('quantity')));
        $this->session->flash('success', 'Jumlah item diperbarui.');
        Response::redirect('/cart');
    }

    public function remove(Request $request): never
    {
        $this->assertCsrf($request);
        $this->guard->requireCustomer();
        $this->cart->remove((int) $this->auth->user()['id'], $this->positiveId($request->input('menu_item_id')));
        $this->session->flash('success', 'Item dihapus dari keranjang.');
        Response::redirect('/cart');
    }

    public function clear(Request $request): never
    {
        $this->assertCsrf($request);
        $this->guard->requireCustomer();
        $this->cart->clear((int) $this->auth->user()['id']);
        $this->session->flash('success', 'Keranjang dikosongkan.');
        Response::redirect('/cart');
    }

    public function selectTable(Request $request): void
    {
        $this->guard->requireCustomer();
        $userId = (int) $this->auth->user()['id'];
        if ($this->visits->findActiveByUserId($userId) !== null) {
            $added = $this->cart->consumePending($userId);
            $this->session->flash('success', $added ? 'Menu ditambahkan ke kunjungan aktif.' : 'Meja kunjungan aktif tetap digunakan.');
            Response::redirect('/cart');
        }

        $this->view->render('customer/select-table', [
            'pageTitle' => 'Pilih Meja',
            'tables' => $this->tables->available(),
            'selectedTableId' => $this->cart->draftTableId($userId),
            'hasPending' => $this->cart->hasPending(),
        ]);
    }

    public function storeTable(Request $request): never
    {
        $this->assertCsrf($request);
        $this->guard->requireCustomer();
        $userId = (int) $this->auth->user()['id'];
        if ($this->visits->findActiveByUserId($userId) !== null) {
            $this->cart->consumePending($userId);
            Response::redirect('/cart');
        }

        $tableId = $this->positiveId($request->input('table_id'));
        if (!$this->tables->isAvailable($tableId)) {
            $this->session->flash('error', 'Meja ini baru saja digunakan pelanggan lain. Pilih meja lain.');
            Response::redirect('/tables/select');
        }

        $this->cart->setDraftTable($userId, $tableId);
        $added = $this->cart->consumePending($userId);
        $this->session->flash('success', $added ? 'Meja dipilih dan menu ditambahkan ke keranjang.' : 'Meja dipilih untuk kunjungan ini.');
        Response::redirect('/cart');
    }

    private function assertCsrf(Request $request): void
    {
        if (!$this->csrf->verify($request->input('_token'))) {
            throw new HttpException(419, 'Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.');
        }
    }

    private function positiveId(string $value): int
    {
        if (!ctype_digit($value) || (int) $value < 1) {
            throw new HttpException(422, 'Data yang dikirim tidak valid.');
        }

        return (int) $value;
    }

    private function validQuantity(string $value): int
    {
        if (!ctype_digit($value) || (int) $value < 1 || (int) $value > 99) {
            throw new HttpException(422, 'Jumlah item harus antara 1 dan 99.');
        }

        return (int) $value;
    }

    private function denyInternalRole(): void
    {
        $user = $this->auth->user();
        if ($user !== null && $user['role'] !== 'customer') {
            throw new HttpException(403, 'Halaman ini hanya untuk pelanggan.');
        }
    }
}
