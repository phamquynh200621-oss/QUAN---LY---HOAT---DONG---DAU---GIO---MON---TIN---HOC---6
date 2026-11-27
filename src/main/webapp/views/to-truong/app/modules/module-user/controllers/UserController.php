<?php
/** Trang quản lý tài khoản và trang phân quyền (xem ma trận). */
final class UserController extends Controller
{
    /** GET /admin/users */
    public function index(array $params = []): void
    {
        $data = UserService::search($this->request->all());

        $this->view('module-user::index', $data + [
            'title'         => 'Quản lý tài khoản',
            'roles'         => Role::listAll(),
            'classes'       => TeacherClass::allClasses(),
            'teachingRoles' => UserService::TEACHING_ROLES,
            'canEdit'       => Permission::can(3, 'Q'),
            'selfId'        => (int) Auth::id(),
            'apiPath'       => url('/api/users'),
            'pagePath'      => url('/admin/users'),
            'baseUrl'       => url('/admin/users'),
            'categoryUrl'   => module_exists('module-category') ? url('/categories/classes') : null,
            'scripts'       => [module_asset('user', 'js/user.js')],
        ]);
    }

    /** GET /admin/roles - vai trò, số tài khoản mỗi vai trò và ma trận phân quyền (chỉ xem). */
    public function roles(array $params = []): void
    {
        $this->view('module-user::roles', Role::matrix() + [
            'title'      => 'Phân quyền',
            'roleCounts' => Role::withUserCounts(),
            'usersUrl'   => url('/admin/users'),
        ]);
    }
}
