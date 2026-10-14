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
use App\Repositories\OperationalRepository;
use App\Services\OperationalService;
use App\Services\RoleGuard;

final class StaffOperationsController
{
    public function __construct(
        private readonly View $view,
        private readonly RoleGuard $guard,
        private readonly Csrf $csrf,
        private readonly SessionStore $session,
        private readonly OperationalRepository $operations,
        private readonly OperationalService $service
    ) {
    }

    public function orders(Request $request): void
    {
        $this->guard->requireStaff();
        $this->view->render('staff/orders', ['pageTitle' => 'Pesanan Masuk', 'orders' => $this->operations->receivedOrders()]);
    }

    public function visits(Request $request): void
    {
        $this->guard->requireStaff();
        $this->view->render('staff/visits', ['pageTitle' => 'Kunjungan Aktif', 'visits' => $this->operations->activeVisits()]);
    }

    /** @param array<string,string> $params */
    public function serve(Request $request, array $params): never
    {
        $this->guard->requireStaff();
        $this->assertCsrf($request);
        $orderId = $this->positiveRouteId($params['id'] ?? '');
        try {
            $changed = $this->service->serve($orderId);
            $this->session->flash('success', $changed ? 'Pesanan berhasil ditandai telah disajikan.' : 'Pesanan sudah disajikan sebelumnya.');
        } catch (CheckoutException $exception) {
            $this->session->flash('error', $exception->getMessage());
        }
        Response::redirect('/staff/orders');
    }

    /** @param array<string,string> $params */
    public function closeVisit(Request $request, array $params): never
    {
        $this->guard->requireStaff();
        $this->assertCsrf($request);
        $visitId = $this->positiveRouteId($params['id'] ?? '');
        try {
            $changed = $this->service->closeVisit($visitId);
            $this->session->flash('success', $changed ? 'Kunjungan ditutup dan meja kembali tersedia.' : 'Kunjungan sudah ditutup sebelumnya.');
        } catch (CheckoutException $exception) {
            $this->session->flash('error', $exception->getMessage());
        }
        Response::redirect('/staff/visits');
    }

    private function assertCsrf(Request $request): void
    {
        if (!$this->csrf->verify($request->input('_token'))) {
            throw new HttpException(419, 'Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.');
        }
    }

    private function positiveRouteId(string $value): int
    {
        if (!ctype_digit($value) || (int) $value < 1) {
            throw new HttpException(404, 'Data tidak ditemukan.');
        }

        return (int) $value;
    }
}
