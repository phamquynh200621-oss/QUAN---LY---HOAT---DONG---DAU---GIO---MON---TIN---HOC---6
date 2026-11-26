<?php
/**
 * Nghiệp vụ đăng nhập / đăng xuất.
 *
 * MDI1.1 Đăng nhập
 *   Input : tên đăng nhập hoặc email, mật khẩu
 *   Process: kiểm tra tài khoản tồn tại, mật khẩu đúng, tài khoản không bị khóa; tạo phiên
 *   Output: chuyển tới trang tổng quan, hoặc thông báo lỗi
 * MDI1.2 Đăng xuất
 *   Process: hủy phiên, ghi nhật ký
 */
final class AuthService
{
    public const MSG_INVALID = 'Tên đăng nhập, email hoặc mật khẩu không đúng.';
    public const MSG_LOCKED  = 'Tài khoản đã bị khóa. Hãy liên hệ quản trị viên.';

    // Bản băm hợp lệ dùng để so sánh khi không có tài khoản, giữ thời gian xử lý gần như nhau
    // (tránh lộ việc tên đăng nhập có tồn tại hay không).
    private const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    /**
     * @return array{ok:bool,status:int,message:string,user?:array<string,mixed>}
     */
    public static function attempt(string $login, string $password): array
    {
        $user  = User::findByLogin($login);
        $valid = password_verify($password, $user['password_hash'] ?? self::DUMMY_HASH);

        if ($user === null || !$valid) {
            Logger::activity('login', 'auth', 'Đăng nhập thất bại với: ' . mb_substr($login, 0, 100), 'failed', null);
            return ['ok' => false, 'status' => 401, 'message' => self::MSG_INVALID];
        }

        if ($user['status'] !== 'active') {
            Logger::activity('login', 'auth', 'Tài khoản bị khóa cố đăng nhập', 'failed', (int) $user['id'], 'user', (int) $user['id']);
            return ['ok' => false, 'status' => 403, 'message' => self::MSG_LOCKED];
        }

        // Nâng cấp thuật toán băm nếu PHP có mặc định mới hơn
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            User::updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        Auth::login($user);
        User::touchLastLogin((int) $user['id']);
        Logger::activity('login', 'auth', 'Đăng nhập thành công', 'success', (int) $user['id'], 'user', (int) $user['id']);

        return [
            'ok'      => true,
            'status'  => 200,
            'message' => 'Đăng nhập thành công.',
            'user'    => [
                'id'        => (int) $user['id'],
                'full_name' => $user['full_name'],
                'role_code' => $user['role_code'],
            ],
        ];
    }

    public static function logout(): void
    {
        if (Auth::check()) {
            Logger::activity('logout', 'auth', 'Đăng xuất', 'success');
        }
        Auth::logout();
    }
}
