<?php
/**
 * API JSON của module-profile.
 *   GET  /api/profile
 *   PUT  /api/profile            {full_name, email, phone}
 *   PUT  /api/profile/password   {old_password, new_password, new_password_confirm}
 *   POST /api/profile/avatar     multipart, trường "avatar"
 */
final class ProfileApiController extends Controller
{
    public function show(array $params = []): void
    {
        $profile = ProfileService::get((int) Auth::id());
        if ($profile === null) {
            $this->error('Không tìm thấy tài khoản.', 404);
        }
        $this->success($profile);
    }

    public function update(array $params = []): void
    {
        $result = ProfileService::update((int) Auth::id(), [
            'full_name' => $this->request->string('full_name'),
            'email'     => $this->request->string('email'),
            'phone'     => $this->request->string('phone'),
        ]);

        if (!$result['ok']) {
            $this->error('Vui lòng kiểm tra lại thông tin.', 422, $result['errors']);
        }
        $this->success($result['profile'], 'Đã cập nhật thông tin cá nhân.');
    }

    public function changePassword(array $params = []): void
    {
        $result = ProfileService::changePassword(
            (int) Auth::id(),
            $this->request->raw('old_password'),
            $this->request->raw('new_password'),
            $this->request->raw('new_password_confirm')
        );

        if (!$result['ok']) {
            $this->error('Vui lòng kiểm tra lại thông tin.', 422, $result['errors']);
        }
        $this->success(null, 'Đã đổi mật khẩu.');
    }

    public function uploadAvatar(array $params = []): void
    {
        $result = ProfileService::saveAvatar((int) Auth::id(), $this->request->file('avatar'));

        if (!$result['ok']) {
            $this->error($result['message'], 422, ['avatar' => $result['message']]);
        }
        $this->success(['avatar_url' => $result['avatar_url']], $result['message']);
    }
}
