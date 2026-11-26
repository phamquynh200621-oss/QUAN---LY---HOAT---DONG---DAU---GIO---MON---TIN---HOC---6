<?php
/** Trang quên mật khẩu và trang đặt lại mật khẩu (mở từ liên kết trong email). */
final class PasswordResetController extends Controller
{
    public function showForgot(array $params = []): void
    {
        $this->view('module-auth::forgot_password', [
            'title'   => 'Quên mật khẩu',
            'scripts' => [module_asset('auth', 'js/auth.js')],
        ], 'layouts/auth');
    }

    public function showReset(array $params = []): void
    {
        $token = $this->request->string('token');

        $this->view('module-auth::reset_password', [
            'title'      => 'Đặt lại mật khẩu',
            'token'      => $token,
            'tokenValid' => PasswordResetService::isTokenValid($token),
            'scripts'    => [module_asset('auth', 'js/auth.js')],
        ], 'layouts/auth');
    }
}
