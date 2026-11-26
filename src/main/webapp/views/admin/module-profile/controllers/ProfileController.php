<?php
/** Trang hồ sơ cá nhân và phục vụ ảnh đại diện của chính người dùng. */
final class ProfileController extends Controller
{
    public function index(array $params = []): void
    {
        $profile = ProfileService::get((int) Auth::id());
        if ($profile === null) {
            Response::abort(404, 'Không tìm thấy tài khoản.', false);
        }

        $this->view('module-profile::index', [
            'title'   => 'Hồ sơ cá nhân',
            'profile' => $profile,
            'styles'  => [module_asset('profile', 'css/profile.css')],
            'scripts' => [module_asset('profile', 'js/profile.js')],
        ]);
    }

    /** GET /profile/avatar - ảnh nằm ngoài thư mục public nên phải đọc qua controller. */
    public function avatar(array $params = []): void
    {
        $fileName = Auth::user()['avatar'] ?? null;
        $path     = $fileName ? ProfileService::avatarPath((string) $fileName) : null;

        if ($path === null || !is_file($path)) {
            Response::abort(404, 'Chưa có ảnh đại diện.', false);
        }

        $info = getimagesize($path);
        header('Content-Type: ' . ($info['mime'] ?? 'application/octet-stream'));
        header('Cache-Control: private, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
}
