<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\SessionStore;
use App\Core\View;
use App\Exceptions\HttpException;
use App\Exceptions\ValidationException;
use App\Services\AuthService;
use App\Services\LoginThrottle;
use App\Services\RegistrationService;
use App\Services\RoleGuard;

final class AuthController
{
    public function __construct(
        private readonly View $view,
        private readonly RegistrationService $registration,
        private readonly AuthService $auth,
        private readonly LoginThrottle $throttle,
        private readonly RoleGuard $guard,
        private readonly Csrf $csrf,
        private readonly SessionStore $session
    ) {
    }

    public function showRegister(Request $request): void
    {
        $this->guard->requireGuest();
        $this->renderRegister(returnTo: $request->query('return_to'));
    }

    public function register(Request $request): void
    {
        $this->guard->requireGuest();
        $this->assertCsrf($request);

        $old = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
        ];

        try {
            $user = $this->registration->register([
                ...$old,
                'password' => $request->input('password'),
                'password_confirmation' => $request->input('password_confirmation'),
            ]);
        } catch (ValidationException $exception) {
            $this->renderRegister($old, $exception->errors(), $request->input('return_to'));

            return;
        }

        $this->auth->loginUser($user);
        $this->session->flash('success', 'Akun berhasil dibuat. Selamat datang di RestoManager.');
        Response::redirect($this->auth->redirectPath($request->input('return_to')));
    }

    public function showLogin(Request $request): void
    {
        $this->guard->requireGuest();
        $this->renderLogin(returnTo: $request->query('return_to'));
    }

    public function login(Request $request): void
    {
        $this->guard->requireGuest();
        $this->assertCsrf($request);

        $email = strtolower($request->input('email'));
        $password = $request->input('password');
        $retryAfter = $this->throttle->retryAfterSeconds();

        if ($retryAfter > 0) {
            $this->renderLogin(
                ['email' => $email],
                'Terlalu banyak percobaan masuk. Coba lagi dalam beberapa menit.',
                $request->input('return_to')
            );

            return;
        }

        if (!$this->auth->attempt($email, $password)) {
            $this->renderLogin(
                ['email' => $email],
                'Email atau password tidak sesuai, atau akun tidak aktif.',
                $request->input('return_to')
            );

            return;
        }

        $this->session->flash('success', 'Anda berhasil masuk.');
        Response::redirect($this->auth->redirectPath($request->input('return_to')));
    }

    public function logout(Request $request): void
    {
        $this->assertCsrf($request);
        $this->guard->requireRoles(['customer', 'staff', 'admin']);
        $this->auth->logout();
        $this->session->flash('success', 'Anda berhasil keluar. Data sesi sebelumnya telah dibersihkan.');
        Response::redirect('/');
    }

    /** @param array<string, string> $old @param array<string, string> $errors */
    private function renderRegister(array $old = [], array $errors = [], string $returnTo = ''): void
    {
        $this->view->render('auth/register', [
            'pageTitle' => 'Daftar Pelanggan',
            'old' => $old,
            'errors' => $errors,
            'returnTo' => $this->safeReturnTo($returnTo),
        ]);
    }

    /** @param array<string, string> $old */
    private function renderLogin(array $old = [], ?string $error = null, string $returnTo = ''): void
    {
        $this->view->render('auth/login', [
            'pageTitle' => 'Masuk',
            'old' => $old,
            'loginError' => $error,
            'returnTo' => $this->safeReturnTo($returnTo),
        ]);
    }

    private function safeReturnTo(string $path): string
    {
        $allowed = ['/cart', '/orders', '/history', '/menu', '/categories', '/profile'];

        return in_array($path, $allowed, true) ? $path : '';
    }

    private function assertCsrf(Request $request): void
    {
        if (!$this->csrf->verify($request->input('_token'))) {
            throw new HttpException(419, 'Sesi formulir berakhir. Muat ulang halaman lalu coba lagi.');
        }
    }
}
