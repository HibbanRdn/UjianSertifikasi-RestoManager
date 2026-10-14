<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Exceptions\HttpException;
use App\Repositories\OrderRepository;
use App\Services\AuthService;
use App\Services\RoleGuard;

final class CustomerPageController
{
    public function __construct(
        private readonly View $view,
        private readonly AuthService $auth,
        private readonly RoleGuard $guard,
        private readonly OrderRepository $orders
    ) {
    }

    public function cart(Request $request): void
    {
        $this->denyInternalRole();
        $this->view->render('customer/cart', [
            'pageTitle' => 'Keranjang',
            'isGuest' => !$this->auth->check(),
        ]);
    }

    public function orders(Request $request): void
    {
        $this->denyInternalRole();
        $user = $this->auth->user();
        $orders = $user !== null && $user['role'] === 'customer'
            ? $this->orders->listOwnedByStatus((int) $user['id'], 'received')
            : [];

        $this->view->render('customer/orders', [
            'pageTitle' => 'Orders',
            'isGuest' => $user === null,
            'orders' => $orders,
        ]);
    }

    public function history(Request $request): void
    {
        $this->denyInternalRole();
        $user = $this->auth->user();
        $orders = $user !== null && $user['role'] === 'customer'
            ? $this->orders->listOwnedByStatus((int) $user['id'], 'served')
            : [];

        $this->view->render('customer/history', [
            'pageTitle' => 'Riwayat',
            'isGuest' => $user === null,
            'orders' => $orders,
        ]);
    }

    public function profile(Request $request): void
    {
        $this->guard->requireCustomer();
        $this->view->render('customer/profile', [
            'pageTitle' => 'Profil',
            'profile' => $this->auth->user(),
        ]);
    }

    private function denyInternalRole(): void
    {
        $user = $this->auth->user();
        if ($user !== null && $user['role'] !== 'customer') {
            throw new HttpException(403, 'Halaman ini hanya untuk pelanggan.');
        }
    }
}
