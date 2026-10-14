<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Repositories\DashboardRepository;
use App\Services\RoleGuard;

final class DashboardController
{
    public function __construct(
        private readonly View $view,
        private readonly RoleGuard $guard,
        private readonly DashboardRepository $dashboard
    ) {
    }

    public function index(Request $request): void
    {
        $this->guard->requireAdmin();
        $this->view->render('admin/dashboard', ['pageTitle' => 'Dashboard Admin'] + $this->dashboard->adminDashboard());
    }
}
