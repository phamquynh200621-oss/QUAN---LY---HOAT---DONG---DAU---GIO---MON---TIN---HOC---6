<?php
/**
 * Model User (bảng users) - phần phục vụ đăng nhập và đặt lại mật khẩu.
 * Thành viên phụ trách module-user sẽ viết thêm UserAdmin cho phần quản trị tài khoản.
 */
final class User extends Model
{
    /** Tìm theo tên đăng nhập hoặc email (không phân biệt hoa thường). @return array<string,mixed>|null */
    public static function findByLogin(string $login): ?array
    {
        return self::one(
            'SELECT u.id, u.username, u.email, u.full_name, u.password_hash, u.status, u.role_id, r.code AS role_code
               FROM users u JOIN roles r ON r.id = u.role_id
              WHERE u.username = ? OR u.email = ?
              LIMIT 1',
            [$login, $login]
        );
    }

    /** Tài khoản đang hoạt động theo email. @return array<string,mixed>|null */
    public static function findActiveByEmail(string $email): ?array
    {
        return self::one(
            "SELECT id, email, full_name FROM users WHERE email = ? AND status = 'active' LIMIT 1",
            [$email]
        );
    }

    public static function touchLastLogin(int $id): void
    {
        self::run('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    public static function updatePassword(int $id, string $passwordHash): void
    {
        self::run('UPDATE users SET password_hash = ? WHERE id = ?', [$passwordHash, $id]);
    }
}
