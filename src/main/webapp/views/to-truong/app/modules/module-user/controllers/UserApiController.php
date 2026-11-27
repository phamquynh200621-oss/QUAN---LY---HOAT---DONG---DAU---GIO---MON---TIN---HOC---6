<?php
/**
 * API JSON của module-user (URL theo mục 6 của kế hoạch).
 *   GET   /api/users                ?q&role_id&status&page
 *   GET   /api/users/{id}
 *   POST  /api/users                {username, email, password, full_name, phone, role_id, class_ids[]}
 *   PUT   /api/users/{id}           {email, full_name, phone, role_id, class_ids[]}
 *   PATCH /api/users/{id}/status    {status: active|locked}
 *   GET   /api/roles
 *   GET   /api/permissions/matrix
 * Quyền kiểm tra bằng middleware ở routes.php (xem = C, ghi = Q).
 */
final class UserApiController extends Controller
{
    public function index(array $params = []): void
    {
        $result = UserService::search($this->request->all());
        $this->success(['items' => $result['items'], 'pagination' => $result['pager']]);
    }

    public function show(array $params): void
    {
        $user = UserService::find($this->id($params));
        if ($user === null) {
            $this->error('Không tìm thấy tài khoản.', 404);
        }
        $this->success($user);
    }

    public function store(array $params = []): void
    {
        $this->respond(UserService::create($this->request->all()));
    }

    public function update(array $params): void
    {
        $this->respond(UserService::update($this->id($params), $this->request->all()));
    }

    public function status(array $params): void
    {
        $this->respond(UserService::setStatus($this->id($params), $this->request->string('status')));
    }

    public function roles(array $params = []): void
    {
        $this->success(['items' => Role::withUserCounts()]);
    }

    public function matrix(array $params = []): void
    {
        $this->success(Role::matrix());
    }

    private function id(array $params): int
    {
        $id = (string) ($params['id'] ?? '');
        if (!ctype_digit($id) || (int) $id < 1) {
            $this->error('Mã không hợp lệ.', 404);
        }
        return (int) $id;
    }

    /** @param array<string,mixed> $result kết quả của UserService */
    private function respond(array $result): void
    {
        if (!$result['ok']) {
            $this->error($result['message'], $result['status'], $result['errors']);
        }
        $this->success($result['item'], $result['message'], $result['status']);
    }
}
