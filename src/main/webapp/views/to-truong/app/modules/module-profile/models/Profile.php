<?php
/** Model Profile: truy vấn bảng users cho các thao tác của chính người dùng đang đăng nhập. */
final class Profile extends Model
{
    /** @return array<string,mixed>|null */
    public static function find(int $userId): ?array
    {
        return self::one(
            'SELECT u.id, u.username, u.email, u.full_name, u.phone, u.avatar, u.last_login_at, u.created_at,
                    r.name AS role_name, r.code AS role_code
               FROM users u JOIN roles r ON r.id = u.role_id
              WHERE u.id = ? LIMIT 1',
            [$userId]
        );
    }

    public static function emailTaken(string $email, int $exceptUserId): bool
    {
        return self::one('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1', [$email, $exceptUserId]) !== null;
    }

    public static function updateInfo(int $userId, string $fullName, string $email, ?string $phone): void
    {
        self::run('UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?', [$fullName, $email, $phone, $userId]);
    }

    public static function updateAvatar(int $userId, ?string $fileName): void
    {
        self::run('UPDATE users SET avatar = ? WHERE id = ?', [$fileName, $userId]);
    }

    public static function passwordHash(int $userId): ?string
    {
        $row = self::one('SELECT password_hash FROM users WHERE id = ? LIMIT 1', [$userId]);
        return $row === null ? null : (string) $row['password_hash'];
    }

    public static function updatePassword(int $userId, string $hash): void
    {
        self::run('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $userId]);
    }
}
