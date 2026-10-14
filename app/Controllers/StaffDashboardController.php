<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Repositories\DashboardRepository;
use App\Services\RoleGuard;

final class StaffDashboardController
{
    public function __construct(
        private readonly View $view,
        private readonly RoleGuard $guard,
        private readonly DashboardRepository $dashboard
    ) {
    }

    public function index(Request $request): void
    {
        $this->guard->requireStaff();
        $this->view->render('staff/dashboard', ['pageTitle' => 'Dashboard Petugas'] + $this->dashboard->staffDashboard());
    }
}
