<?php

declare(strict_types=1);

use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\ManagementController as AdminManagementController;
use App\Controllers\AuthController;
use App\Controllers\CustomerPageController;
use App\Controllers\CustomerCartController;
use App\Controllers\CheckoutController;
use App\Controllers\HomeController;
use App\Controllers\StaffDashboardController;
use App\Controllers\StaffOperationsController;
use App\Core\Router;

return static function (array $app): Router {
    $router = new Router();

    $home = new HomeController($app['view'], $app['repositories']['categories'], $app['repositories']['menu_items']);
    $auth = new AuthController(
        $app['view'],
        $app['services']['registration'],
        $app['auth'],
        $app['throttle'],
        $app['guard'],
        $app['csrf'],
        $app['session']
    );
    $customer = new CustomerPageController(
        $app['view'],
        $app['auth'],
        $app['guard'],
        $app['repositories']['orders']
    );
    $cart = new CustomerCartController(
        $app['view'],
        $app['auth'],
        $app['guard'],
        $app['csrf'],
        $app['session'],
        $app['services']['cart'],
        $app['repositories']['menu_items'],
        $app['repositories']['tables'],
        $app['repositories']['visits']
    );
    $checkout = new CheckoutController(
        $app['view'], $app['auth'], $app['guard'], $app['csrf'], $app['session'],
        $app['services']['checkout'], $app['repositories']['orders']
    );
    $staff = new StaffDashboardController($app['view'], $app['guard'], $app['repositories']['dashboard']);
    $staffOperations = new StaffOperationsController(
        $app['view'], $app['guard'], $app['csrf'], $app['session'],
        $app['repositories']['operations'], $app['services']['operations']
    );
    $admin = new AdminDashboardController($app['view'], $app['guard'], $app['repositories']['dashboard']);
    $adminManagement = new AdminManagementController(
        $app['view'], $app['guard'], $app['csrf'], $app['session'],
        $app['repositories']['admin'], $app['services']['admin']
    );

    $router->get('/', [$home, 'index']);
    $router->get('/categories', [$home, 'categories']);
    $router->get('/menu', [$home, 'menu']);
    $router->get('/register', [$auth, 'showRegister']);
    $router->post('/register', [$auth, 'register']);
    $router->get('/login', [$auth, 'showLogin']);
    $router->post('/login', [$auth, 'login']);
    $router->post('/logout', [$auth, 'logout']);
    $router->get('/cart', [$cart, 'show']);
    $router->post('/cart/items', [$cart, 'add']);
    $router->post('/cart/update', [$cart, 'update']);
    $router->post('/cart/remove', [$cart, 'remove']);
    $router->post('/cart/clear', [$cart, 'clear']);
    $router->get('/tables/select', [$cart, 'selectTable']);
    $router->post('/tables/select', [$cart, 'storeTable']);
    $router->get('/checkout/review', [$checkout, 'review']);
    $router->get('/checkout/payment', [$checkout, 'payment']);
    $router->get('/checkout/payment/qris', [$checkout, 'qrisPayment']);
    $router->get('/checkout/payment/cash', [$checkout, 'cashPayment']);
    $router->post('/checkout/finalize', [$checkout, 'finalize']);
    $router->get('/orders/{id}/receipt', [$checkout, 'receipt']);
    $router->get('/orders', [$customer, 'orders']);
    $router->get('/history', [$customer, 'history']);
    $router->get('/profile', [$customer, 'profile']);
    $router->get('/staff/dashboard', [$staff, 'index']);
    $router->get('/staff/orders', [$staffOperations, 'orders']);
    $router->post('/staff/orders/{id}/serve', [$staffOperations, 'serve']);
    $router->get('/staff/visits', [$staffOperations, 'visits']);
    $router->post('/staff/visits/{id}/close', [$staffOperations, 'closeVisit']);
    $router->get('/admin/dashboard', [$admin, 'index']);
    $router->get('/admin/administration', [$adminManagement, 'administration']);
    $router->get('/admin/categories', [$adminManagement, 'categories']);
    $router->post('/admin/categories/save', [$adminManagement, 'saveCategory']);
    $router->post('/admin/categories/toggle', [$adminManagement, 'toggleCategory']);
    $router->get('/admin/menu', [$adminManagement, 'menu']);
    $router->post('/admin/menu/save', [$adminManagement, 'saveMenu']);
    $router->post('/admin/menu/toggle', [$adminManagement, 'toggleMenu']);
    $router->get('/admin/tables', [$adminManagement, 'tables']);
    $router->post('/admin/tables/save', [$adminManagement, 'saveTable']);
    $router->post('/admin/tables/toggle', [$adminManagement, 'toggleTable']);
    $router->get('/admin/staff', [$adminManagement, 'staff']);
    $router->post('/admin/staff/save', [$adminManagement, 'saveStaff']);
    $router->post('/admin/staff/toggle', [$adminManagement, 'toggleStaff']);
    $router->get('/admin/reports', [$adminManagement, 'reports']);

    return $router;
};
