<?php
/**
 * API JSON của module-auth (xem mục 6 trong tài liệu kế hoạch).
 *   POST /api/auth/login            {login, password}
 *   POST /api/auth/logout
 *   POST /api/auth/forgot-password  {email}
 *   POST /api/auth/reset-password   {token, password, password_confirm}
 */
final class AuthApiController extends Controller
{
    public function login(array $params = []): void
    {
        $login    = $this->request->string('login');
        $password = $this->request->raw('password');

        $errors = Validator::validate(
            ['login' => $login, 'password' => $password],
            ['login' => 'required|max:150', 'password' => 'required'],
            ['login' => 'Tên đăng nhập hoặc email', 'password' => 'Mật khẩu']
        );
        if ($errors !== []) {
            $this->error('Vui lòng nhập đầy đủ thông tin.', 422, $errors);
        }

        $result = AuthService::attempt($login, $password);
        if (!$result['ok']) {
            $this->error($result['message'], $result['status']);
        }

        $this->success(['user' => $result['user'], 'redirect' => url('/dashboard')], $result['message']);
    }

    public function logout(array $params = []): void
    {
        AuthService::logout();
        $this->success(['redirect' => url('/login')], 'Bạn đã đăng xuất.');
    }

    public function forgotPassword(array $params = []): void
    {
        $email  = $this->request->string('email');
        $errors = Validator::validate(['email' => $email], ['email' => 'required|email|max:150'], ['email' => 'Email']);
        if ($errors !== []) {
            $this->error('Email chưa hợp lệ.', 422, $errors);
        }

        PasswordResetService::request($email);
        $this->success(null, PasswordResetService::MSG_SENT);   // luôn cùng một thông báo
    }

    public function resetPassword(array $params = []): void
    {
        $data = [
            'token'            => $this->request->string('token'),
            'password'         => $this->request->raw('password'),
            'password_confirm' => $this->request->raw('password_confirm'),
        ];
        $errors = Validator::validate(
            $data,
            ['password' => 'required|min:8|max:72', 'password_confirm' => 'required|same:password'],
            ['password' => 'Mật khẩu mới', 'password_confirm' => 'Xác nhận mật khẩu']
        );
        if ($errors !== []) {
            $this->error('Vui lòng kiểm tra lại thông tin.', 422, $errors);
        }

        if (!PasswordResetService::reset($data['token'], $data['password'])) {
            $this->error(PasswordResetService::MSG_INVALID, 422);
        }

        Session::flash('success', 'Đã đặt lại mật khẩu. Hãy đăng nhập bằng mật khẩu mới.');
        $this->success(['redirect' => url('/login')], 'Đã đặt lại mật khẩu.');
    }
}
