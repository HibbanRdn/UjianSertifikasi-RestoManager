<?php

declare(strict_types=1);

use App\Core\Request;
use App\Exceptions\HttpException;

$app = require dirname(__DIR__) . '/bootstrap/app.php';

$app['view']->share('authUser', $app['auth']->user());
$app['view']->share('csrfToken', $app['csrf']->token());
$app['view']->share('flashSuccess', $app['session']->pullFlash('success'));
$app['view']->share('flashError', $app['session']->pullFlash('error'));
$app['view']->share('currentPath', parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

$buildRouter = require dirname(__DIR__) . '/config/routes.php';
$router = $buildRouter($app);

try {
    $router->dispatch(Request::capture());
} catch (HttpException $exception) {
    http_response_code($exception->statusCode());
    $app['view']->render('errors/http', [
        'pageTitle' => 'Terjadi Kendala',
        'statusCode' => $exception->statusCode(),
        'message' => $exception->getMessage(),
    ]);
} catch (Throwable $exception) {
    error_log(sprintf('[RestoManager] %s in %s:%d', $exception->getMessage(), $exception->getFile(), $exception->getLine()));
    http_response_code(500);
    $app['view']->render('errors/http', [
        'pageTitle' => 'Terjadi Kendala',
        'statusCode' => 500,
        'message' => 'Sistem belum dapat memproses permintaan. Silakan coba kembali.',
    ]);
}
