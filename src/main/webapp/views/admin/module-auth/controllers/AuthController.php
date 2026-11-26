<?php
/** Các trang của module-auth: hiển thị form đăng nhập và xử lý đăng xuất từ menu. */
final class AuthController extends Controller
{
    public function showLogin(array $params = []): void
    {
        $this->view('module-auth::login', [
            'title'   => 'Đăng nhập',
            'scripts' => [module_asset('auth', 'js/auth.js')],
        ], 'layouts/auth');
    }

    /** POST /logout (form trong menu người dùng, có token CSRF). */
    public function logout(array $params = []): void
    {
        AuthService::logout();
        Session::flash('success', 'Bạn đã đăng xuất.');
        $this->redirect('/login');
    }
}
