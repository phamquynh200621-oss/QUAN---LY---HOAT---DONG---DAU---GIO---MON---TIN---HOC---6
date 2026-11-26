<?php
/** Model PasswordReset (bảng password_resets): token đặt lại mật khẩu, chỉ lưu bản băm SHA-256. */
final class PasswordReset extends Model
{
    public static function create(int $userId, string $tokenHash, int $minutes): void
    {
        self::insert(
            'INSERT INTO password_resets (user_id, token_hash, expires_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))',
            [$userId, $tokenHash, $minutes]
        );
    }

    /** Xóa các token chưa dùng của người dùng (mỗi lần yêu cầu mới chỉ còn một liên kết hợp lệ). */
    public static function deleteUnusedByUser(int $userId): void
    {
        self::run('DELETE FROM password_resets WHERE user_id = ? AND used_at IS NULL', [$userId]);
    }

    /** Token còn hạn, chưa dùng, của tài khoản còn hoạt động. @return array<string,mixed>|null */
    public static function findValid(string $tokenHash): ?array
    {
        return self::one(
            "SELECT pr.id, pr.user_id
               FROM password_resets pr JOIN users u ON u.id = pr.user_id
              WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at > NOW() AND u.status = 'active'
              LIMIT 1",
            [$tokenHash]
        );
    }

    public static function markUsed(int $id): void
    {
        self::run('UPDATE password_resets SET used_at = NOW() WHERE id = ?', [$id]);
    }
}
