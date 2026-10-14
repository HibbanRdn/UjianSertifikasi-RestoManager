<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\SessionStore;
use App\Core\View;
use App\Exceptions\CheckoutException;
use App\Exceptions\HttpException;
use App\Repositories\AdminRepository;
use App\Services\AdminService;
use App\Services\RoleGuard;

final class ManagementController
{
    public function __construct(
        private readonly View $view,
        private readonly RoleGuard $guard,
        private readonly Csrf $csrf,
        private readonly SessionStore $session,
        private readonly AdminRepository $repository,
        private readonly AdminService $service
    ) {
    }

    public function categories(Request $request): void
    {
        $this->guard->requireAdmin();
        $this->view->render('admin/categories', ['pageTitle' => 'Kelola Kategori', 'categories' => $this->repository->categories()]);
    }

    public function administration(Request $request): void
    {
        $this->guard->requireAdmin();
        $this->view->render('admin/administration', [
            'pageTitle' => 'Pusat Administrasi',
            'counts' => $this->repository->administrationCounts(),
        ]);
    }

    public function saveCategory(Request $request): never
    {
        $this->mutate($request, '/admin/categories', fn () => $this->service->saveCategory($this->optionalId($request->input('id')), $request->input('name')), 'Kategori berhasil disimpan.');
    }

    public function toggleCategory(Request $request): never
    {
        $this->mutate($request, '/admin/categories', fn () => $this->service->toggleCategory($this->requiredId($request->input('id'))), 'Status kategori diperbarui.');
    }

    public function menu(Request $request): void
    {
        $this->guard->requireAdmin();
        $this->view->render('admin/menu', ['pageTitle' => 'Kelola Menu', 'items' => $this->repository->menuItems(), 'categories' => $this->repository->categories()]);
    }

    public function saveMenu(Request $request): never
    {
        $data = ['category_id' => $request->input('category_id'), 'name' => $request->input('name'), 'description' => $request->input('description'), 'price_idr' => $request->input('price_idr')];
        $this->mutate($request, '/admin/menu', fn () => $this->service->saveMenu($this->optionalId($request->input('id')), $data, $request->file('image')), 'Menu berhasil disimpan.');
    }

    public function toggleMenu(Request $request): never
    {
        $field = $request->input('field');
        $this->mutate($request, '/admin/menu', fn () => $this->service->toggleMenu($this->requiredId($request->input('id')), $field), 'Status menu diperbarui.');
    }

    public function tables(Request $request): void
    {
        $this->guard->requireAdmin();
        $this->view->render('admin/tables', ['pageTitle' => 'Kelola Meja', 'tables' => $this->repository->tables()]);
    }

    public function saveTable(Request $request): never
    {
        $this->mutate($request, '/admin/tables', fn () => $this->service->saveTable($this->optionalId($request->input('id')), $request->input('table_code')), 'Meja berhasil disimpan.');
    }

    public function toggleTable(Request $request): never
    {
        $this->mutate($request, '/admin/tables', fn () => $this->service->toggleTable($this->requiredId($request->input('id'))), 'Status meja diperbarui.');
    }

    public function staff(Request $request): void
    {
        $this->guard->requireAdmin();
        $this->view->render('admin/staff', ['pageTitle' => 'Kelola Petugas', 'staff' => $this->repository->staff()]);
    }

    public function saveStaff(Request $request): never
    {
        $data = ['name' => $request->input('name'), 'email' => $request->input('email'), 'phone' => $request->input('phone'), 'password' => $request->input('password')];
        $this->mutate($request, '/admin/staff', fn () => $this->service->saveStaff($this->optionalId($request->input('id')), $data), 'Akun Petugas berhasil disimpan.');
    }

    public function toggleStaff(Request $request): never
    {
        $this->mutate($request, '/admin/staff', fn () => $this->service->toggleStaff($this->requiredId($request->input('id'))), 'Status akun Petugas diperbarui.');
    }

    public function reports(Request $request): void
    {
        $this->guard->requireAdmin();
        $defaultFrom = date('Y-m-01');
        $today = date('Y-m-d');
        $from = $this->validDate($request->query('from')) ?? $defaultFrom;
        $to = $this->validDate($request->query('to')) ?? $today;
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $this->view->render('admin/reports', ['pageTitle' => 'Laporan Simulasi', 'from' => $from, 'to' => $to] + $this->repository->report($from, $to));
    }

    private function mutate(Request $request, string $redirect, callable $action, string $success): never
    {
        $this->guard->requireAdmin();
        if (!$this->csrf->verify($request->input('_token'))) {
            throw new HttpException(419, 'Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.');
        }
        try {
            $action();
            $this->session->flash('success', $success);
        } catch (CheckoutException $exception) {
            $this->session->flash('error', $exception->getMessage());
        }
        Response::redirect($redirect);
    }

    private function requiredId(string $value): int
    {
        if (!ctype_digit($value) || (int) $value < 1) {
            throw new CheckoutException('Referensi data tidak valid.');
        }

        return (int) $value;
    }

    private function optionalId(string $value): ?int
    {
        return $value === '' ? null : $this->requiredId($value);
    }

    private function validDate(string $value): ?string
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }
}
