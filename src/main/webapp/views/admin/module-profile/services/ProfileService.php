<?php
/**
 * Nghiệp vụ hồ sơ cá nhân.
 *
 * MDI1.3 Xem thông tin cá nhân
 *   Process: đọc tài khoản của người đang đăng nhập (tên đăng nhập và vai trò chỉ xem, không sửa được)
 * Cập nhật thông tin cá nhân (dòng 2 bảng phân quyền)
 *   Input : họ tên, email, số điện thoại, ảnh đại diện, hoặc đổi mật khẩu (mật khẩu hiện tại + mật khẩu mới)
 *   Process: kiểm tra hợp lệ, email không trùng tài khoản khác, ghi nhật ký
 *   Output: hồ sơ đã cập nhật hoặc lỗi theo từng trường
 */
final class ProfileService
{
    private const AVATAR_MAX_BYTES = 2097152; // 2 MB
    private const AVATAR_TYPES     = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /** @return array<string,mixed>|null */
    public static function get(int $userId): ?array
    {
        $row = Profile::find($userId);
        return $row === null ? null : self::present($row);
    }

    /**
     * @param array<string,string> $input full_name, email, phone
     * @return array{ok:bool,errors:array<string,string>,profile?:array<string,mixed>}
     */
    public static function update(int $userId, array $input): array
    {
        $data = [
            'full_name' => trim($input['full_name'] ?? ''),
            'email'     => trim($input['email'] ?? ''),
            'phone'     => trim($input['phone'] ?? ''),
        ];

        $errors = Validator::validate(
            $data,
            ['full_name' => 'required|max:150', 'email' => 'required|email|max:150', 'phone' => 'max:20'],
            ['full_name' => 'Họ và tên', 'email' => 'Email', 'phone' => 'Số điện thoại']
        );

        if (!isset($errors['phone']) && $data['phone'] !== '' && !preg_match('/^[0-9+\s().-]{8,20}$/', $data['phone'])) {
            $errors['phone'] = 'Số điện thoại không hợp lệ.';
        }
        if (!isset($errors['email']) && Profile::emailTaken($data['email'], $userId)) {
            $errors['email'] = 'Email này đang được dùng cho tài khoản khác.';
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        Profile::updateInfo($userId, $data['full_name'], $data['email'], $data['phone'] === '' ? null : $data['phone']);
        Logger::activity('update_profile', 'profile', 'Cập nhật thông tin cá nhân', 'success', $userId, 'user', $userId);

        return ['ok' => true, 'errors' => [], 'profile' => self::get($userId)];
    }

    /** @return array{ok:bool,errors:array<string,string>} */
    public static function changePassword(int $userId, string $old, string $new, string $confirm): array
    {
        $errors = Validator::validate(
            ['old_password' => $old, 'new_password' => $new, 'new_password_confirm' => $confirm],
            [
                'old_password'         => 'required',
                'new_password'         => 'required|min:8|max:72',
                'new_password_confirm' => 'required|same:new_password',
            ],
            ['old_password' => 'Mật khẩu hiện tại', 'new_password' => 'Mật khẩu mới', 'new_password_confirm' => 'Xác nhận mật khẩu mới']
        );

        if (!isset($errors['old_password'])) {
            $hash = Profile::passwordHash($userId);
            if ($hash === null || !password_verify($old, $hash)) {
                $errors['old_password'] = 'Mật khẩu hiện tại không đúng.';
            }
        }
        if (!isset($errors['new_password']) && !isset($errors['old_password']) && $new === $old) {
            $errors['new_password'] = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        Profile::updatePassword($userId, password_hash($new, PASSWORD_DEFAULT));
        Session::regenerate();   // đổi mã phiên sau khi đổi mật khẩu, người dùng vẫn đăng nhập
        Logger::activity('change_password', 'profile', 'Đổi mật khẩu', 'success', $userId, 'user', $userId);

        return ['ok' => true, 'errors' => []];
    }

    /**
     * @param array<string,mixed>|null $file phần tử của $_FILES
     * @return array{ok:bool,message:string,avatar_url?:string}
     */
    public static function saveAvatar(int $userId, ?array $file): array
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'message' => 'Hãy chọn một ảnh.'];
        }
        if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            return ['ok' => false, 'message' => 'Ảnh không được vượt quá 2 MB.'];
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'message' => 'Tải ảnh lên thất bại, hãy thử lại.'];
        }
        if ($file['size'] > self::AVATAR_MAX_BYTES) {
            return ['ok' => false, 'message' => 'Ảnh không được vượt quá 2 MB.'];
        }

        $info = @getimagesize($file['tmp_name']);
        $mime = $info['mime'] ?? '';
        if ($info === false || !isset(self::AVATAR_TYPES[$mime])) {
            return ['ok' => false, 'message' => 'Chỉ nhận ảnh JPG, PNG hoặc WebP.'];
        }

        $dir = STORAGE_PATH . '/uploads/avatars';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            Logger::error('Không tạo được thư mục ' . $dir);
            return ['ok' => false, 'message' => 'Không lưu được ảnh trên máy chủ.'];
        }

        $name = bin2hex(random_bytes(16)) . '.' . self::AVATAR_TYPES[$mime];
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            return ['ok' => false, 'message' => 'Không lưu được ảnh trên máy chủ.'];
        }

        $old = Profile::find($userId)['avatar'] ?? null;
        Profile::updateAvatar($userId, $name);
        if ($old) {
            @unlink(self::avatarPath((string) $old));
        }
        Logger::activity('update_avatar', 'profile', 'Cập nhật ảnh đại diện', 'success', $userId, 'user', $userId);

        return ['ok' => true, 'message' => 'Đã cập nhật ảnh đại diện.', 'avatar_url' => self::avatarUrl($name)];
    }

    /** Đường dẫn thật của ảnh (chỉ lấy tên tệp để không đi ra ngoài thư mục avatars). */
    public static function avatarPath(string $fileName): string
    {
        return STORAGE_PATH . '/uploads/avatars/' . basename($fileName);
    }

    public static function avatarUrl(?string $fileName): ?string
    {
        return $fileName ? url('/profile/avatar') . '?v=' . substr(md5($fileName), 0, 8) : null;
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private static function present(array $row): array
    {
        return [
            'id'            => (int) $row['id'],
            'username'      => $row['username'],
            'full_name'     => $row['full_name'],
            'email'         => $row['email'],
            'phone'         => $row['phone'],
            'role_name'     => $row['role_name'],
            'avatar_url'    => self::avatarUrl($row['avatar']),
            'last_login_at' => $row['last_login_at'],
        ];
    }
}
