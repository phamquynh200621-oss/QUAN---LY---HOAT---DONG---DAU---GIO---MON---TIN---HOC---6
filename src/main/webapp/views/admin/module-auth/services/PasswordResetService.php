<?php
/**
 * Nghiệp vụ quên mật khẩu (giả định G5): gửi liên kết có token, hiệu lực theo config app.password_reset_minutes.
 * Token gửi cho người dùng là chuỗi ngẫu nhiên; trong CSDL chỉ lưu bản băm SHA-256 của nó.
 */
final class PasswordResetService
{
    public const MSG_SENT    = 'Nếu email này thuộc một tài khoản đang hoạt động, hệ thống đã gửi hướng dẫn đặt lại mật khẩu.';
    public const MSG_INVALID = 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.';

    /** Luôn kết thúc êm lặng dù email có tồn tại hay không (không lộ thông tin tài khoản). */
    public static function request(string $email): void
    {
        $user = User::findActiveByEmail($email);
        if ($user === null) {
            Logger::activity('forgot_password', 'auth', 'Yêu cầu đặt lại mật khẩu cho email không có tài khoản hoạt động', 'failed', null);
            return;
        }

        $minutes = (int) Config::get('app.password_reset_minutes', 30);
        $token   = bin2hex(random_bytes(32));

        PasswordReset::deleteUnusedByUser((int) $user['id']);
        PasswordReset::create((int) $user['id'], hash('sha256', $token), $minutes);

        $link = absolute_url('/reset-password?token=' . $token);
        Mailer::send(
            $user['email'],
            'Đặt lại mật khẩu',
            "Xin chào {$user['full_name']},\n\n"
            . "Mở liên kết sau để đặt mật khẩu mới (có hiệu lực {$minutes} phút):\n{$link}\n\n"
            . 'Nếu bạn không yêu cầu, hãy bỏ qua thư này.'
        );

        Logger::activity('forgot_password', 'auth', 'Đã tạo liên kết đặt lại mật khẩu', 'success', (int) $user['id'], 'user', (int) $user['id']);
    }

    public static function isTokenValid(string $token): bool
    {
        return $token !== '' && PasswordReset::findValid(hash('sha256', $token)) !== null;
    }

    /** @return bool true nếu đặt lại thành công */
    public static function reset(string $token, string $newPassword): bool
    {
        $row = $token === '' ? null : PasswordReset::findValid(hash('sha256', $token));
        if ($row === null) {
            return false;
        }

        Database::transaction(static function () use ($row, $newPassword): void {
            User::updatePassword((int) $row['user_id'], password_hash($newPassword, PASSWORD_DEFAULT));
            PasswordReset::markUsed((int) $row['id']);
        });

        Logger::activity('reset_password', 'auth', 'Đặt lại mật khẩu thành công', 'success', (int) $row['user_id'], 'user', (int) $row['user_id']);
        return true;
    }
}
