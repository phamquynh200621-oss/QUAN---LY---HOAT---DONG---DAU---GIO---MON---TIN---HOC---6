<?php
/** BẢN TẠM: chỉ chào người dùng. Sẽ được thay bằng dashboard theo từng vai trò (module-dashboard). */
final class DashboardController extends Controller
{
    public function index(array $params = []): void
    {
        $this->view('module-dashboard::index', [
            'title' => 'Tổng quan',
            'user'  => Auth::user(),
        ]);
    }
}
