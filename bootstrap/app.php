<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Environment;
use App\Core\Csrf;
use App\Core\NativeSession;
use App\Core\View;
use App\Repositories\CategoryRepository;
use App\Repositories\DashboardRepository;
use App\Repositories\DiningTableRepository;
use App\Repositories\MenuItemRepository;
use App\Repositories\OrderRepository;
use App\Repositories\OperationalRepository;
use App\Repositories\AdminRepository;
use App\Repositories\UserRepository;
use App\Repositories\VisitRepository;
use App\Services\AuthService;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OperationalService;
use App\Services\AdminService;
use App\Services\LoginThrottle;
use App\Services\MenuImageUploadService;
use App\Services\RegistrationService;
use App\Services\RoleGuard;
use App\Services\RolePolicy;

$projectRoot = dirname(__DIR__);

require $projectRoot . '/vendor/autoload.php';

Environment::load($projectRoot . '/.env');

$appConfig = require $projectRoot . '/config/app.php';
$databaseConfig = require $projectRoot . '/config/database.php';

date_default_timezone_set((string) $appConfig['timezone']);

$database = new Database($databaseConfig);
$pdo = $database->connect();
$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$session = NativeSession::start($isHttps);
$csrf = new Csrf($session);

$userRepository = new UserRepository($pdo);
$throttle = new LoginThrottle($session);
$auth = new AuthService($userRepository, $session, $throttle);
$rolePolicy = new RolePolicy();
$guard = new RoleGuard($auth, $rolePolicy);
$view = new View($projectRoot . '/views');
$menuItemRepository = new MenuItemRepository($pdo);
$tableRepository = new DiningTableRepository($pdo);
$visitRepository = new VisitRepository($pdo);
$cartService = new CartService($session, $menuItemRepository);
$checkoutService = new CheckoutService(
    $pdo,
    $session,
    $cartService,
    $tableRepository,
    $visitRepository,
    (int) $appConfig['order_estimate_min'],
    (int) $appConfig['order_estimate_max']
);
$uploadConfig = $appConfig['menu_upload'];
$menuImageUpload = new MenuImageUploadService(
    (string) $uploadConfig['directory'],
    (string) $uploadConfig['public_prefix'],
    (int) $uploadConfig['max_bytes'],
    (int) $uploadConfig['max_dimension']
);

return [
    'root' => $projectRoot,
    'config' => $appConfig,
    'database' => $database,
    'pdo' => $pdo,
    'session' => $session,
    'csrf' => $csrf,
    'view' => $view,
    'auth' => $auth,
    'guard' => $guard,
    'role_policy' => $rolePolicy,
    'throttle' => $throttle,
    'repositories' => [
        'users' => $userRepository,
        'categories' => new CategoryRepository($pdo),
        'menu_items' => $menuItemRepository,
        'tables' => $tableRepository,
        'visits' => $visitRepository,
        'orders' => new OrderRepository($pdo),
        'dashboard' => new DashboardRepository($pdo),
        'operations' => new OperationalRepository($pdo),
        'admin' => new AdminRepository($pdo),
    ],
    'services' => [
        'registration' => new RegistrationService($userRepository),
        'cart' => $cartService,
        'checkout' => $checkoutService,
        'operations' => new OperationalService($pdo),
        'admin' => new AdminService($pdo, $menuImageUpload),
    ],
];
