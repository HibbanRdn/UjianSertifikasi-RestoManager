<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\SessionStore;
use App\Core\View;
use App\Exceptions\CheckoutException;
use App\Exceptions\HttpException;
use App\Repositories\OrderRepository;
use App\Services\AuthService;
use App\Services\CheckoutService;
use App\Services\RoleGuard;

final class CheckoutController
{
    public function __construct(
        private readonly View $view,
        private readonly AuthService $auth,
        private readonly RoleGuard $guard,
        private readonly Csrf $csrf,
        private readonly SessionStore $session,
        private readonly CheckoutService $checkout,
        private readonly OrderRepository $orders
    ) {
    }

    public function review(Request $request): void
    {
        $this->guard->requireCustomer();
        try {
            $data = $this->checkout->prepareReview($this->auth->user());
            $this->view->render('checkout/review', ['pageTitle' => 'Ringkasan Pesanan'] + $data);
        } catch (CheckoutException $exception) {
            $this->session->flash('error', $exception->getMessage());
            Response::redirect($exception->reason() === 'table_required' ? '/tables/select' : '/cart');
        }
    }

    public function payment(Request $request): void
    {
        $this->guard->requireCustomer();
        try {
            $data = $this->checkout->paymentDraft((int) $this->auth->user()['id']);
            $this->view->render('checkout/payment', ['pageTitle' => 'Pembayaran Simulasi'] + $data);
        } catch (CheckoutException $exception) {
            $this->session->flash('error', $exception->getMessage());
            Response::redirect('/checkout/review');
        }
    }

    public function qrisPayment(Request $request): void
    {
        $this->renderPaymentMethod('qris', 'QRIS — Simulasi');
    }

    public function cashPayment(Request $request): void
    {
        $this->renderPaymentMethod('cash', 'Tunai — Simulasi');
    }

    public function finalize(Request $request): never
    {
        $this->assertCsrf($request);
        $this->guard->requireCustomer();

        try {
            $orderId = $this->checkout->finalize(
                $this->auth->user(),
                $request->input('method'),
                $request->input('idempotency_key'),
                $request->input('fingerprint')
            );
            $this->session->flash('success', 'Pesanan berhasil dibuat. Ini adalah transaksi simulasi.');
            Response::redirect('/orders/' . $orderId . '/receipt');
        } catch (CheckoutException $exception) {
            $this->session->flash('error', $exception->getMessage());
            $target = match ($exception->reason()) {
                'table_taken', 'table_required' => '/tables/select',
                'changed_review' => '/checkout/review',
                'empty_cart' => '/cart',
                default => '/checkout/payment',
            };
            Response::redirect($target);
        }
    }

    /** @param array<string,string> $params */
    public function receipt(Request $request, array $params): void
    {
        $this->guard->requireCustomer();
        $orderId = ctype_digit($params['id'] ?? '') ? (int) $params['id'] : 0;
        $receipt = $this->orders->receiptOwnedBy($orderId, (int) $this->auth->user()['id']);
        if ($receipt === null) {
            throw new HttpException(404, 'Bukti pesanan tidak ditemukan atau bukan milik Anda.');
        }

        $this->view->render('checkout/receipt', ['pageTitle' => 'Bukti Pesanan', 'receipt' => $receipt]);
    }

    private function assertCsrf(Request $request): void
    {
        if (!$this->csrf->verify($request->input('_token'))) {
            throw new HttpException(419, 'Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.');
        }
    }

    private function renderPaymentMethod(string $view, string $title): void
    {
        $this->guard->requireCustomer();
        try {
            $data = $this->checkout->paymentDraft((int) $this->auth->user()['id']);
            $this->view->render('checkout/payment-' . $view, ['pageTitle' => $title] + $data);
        } catch (CheckoutException $exception) {
            $this->session->flash('error', $exception->getMessage());
            Response::redirect('/checkout/review');
        }
    }
}
