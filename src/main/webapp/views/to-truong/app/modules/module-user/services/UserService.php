<?php
/**
 * Nghiệp vụ quản lý tài khoản (dòng 3 của bảng phân quyền).
 *
 * MDI2.1 Tạo tài khoản mới và cấp quyền
 *   Input : tên đăng nhập, email, mật khẩu ban đầu, họ tên, vai trò, lớp dạy (với Tổ trưởng/Giáo viên)
 *   Process: kiểm tra hợp lệ, tên đăng nhập và email không trùng, vai trò tồn tại, băm mật khẩu, lưu
 *   Output: tài khoản mới hoặc lỗi theo từng trường
 * MDI2.2 Cập nhật, khóa / mở khóa tài khoản
 *   Process: sửa họ tên, email, SĐT, vai trò, lớp dạy; đổi trạng thái hoạt động/khóa. Tài khoản khóa không đăng nhập được.
 *
 * Quy tắc an toàn: không tự khóa hoặc tự đổi vai trò của chính mình; luôn còn ít nhất một Admin đang hoạt động.
 * Tên đăng nhập không đổi sau khi tạo. Chưa có chức năng Admin đặt lại mật khẩu cho người khác
 * (file đặc tả không nêu) - người dùng tự dùng "Quên mật khẩu".
 *
 * Kết quả: ['ok'=>bool, 'status'=>int, 'message'=>string, 'errors'=>array, 'item'=>mixed]
 */
final class UserService
{
    public const PER_PAGE = 20;
    /** Vai trò có phạm vi "lớp dạy" nên được gán lớp. */
    public const TEACHING_ROLES = ['TTCM', 'GV'];

    public const LEVELS = ['Q' => 'Toàn quyền', 'D' => 'Phê duyệt', 'T' => 'Thao tác', 'C' => 'Chỉ xem', 'K' => 'Không có'];
    public const SCOPES = [
        'all' => '', 'own' => 'cá nhân', 'class' => 'lớp dạy', 'team' => 'toàn tổ',
        'team_level' => 'cấp tổ', 'school' => 'cấp trường',
    ];

    /**
     * @param array<string,mixed> $input thường là $request->all()
     * @return array{items:array<int,array<string,mixed>>,filters:array<string,mixed>,pager:array<string,int>}
     */
    public static function search(array $input): array
    {
        $q      = isset($input['q']) && is_scalar($input['q']) ? trim((string) $input['q']) : '';
        $roleId = isset($input['role_id']) && is_scalar($input['role_id']) && ctype_digit((string) $input['role_id']) && (int) $input['role_id'] > 0
            ? (int) $input['role_id'] : null;
        $status = isset($input['status']) && in_array($input['status'], ['active', 'locked'], true) ? (string) $input['status'] : null;

        $filters = ['q' => $q === '' ? null : mb_substr($q, 0, 100), 'role_id' => $roleId, 'status' => $status];

        $total = UserAdmin::count($filters);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($pages, max(1, (int) ($input['page'] ?? 1)));
        $pager = ['page' => $page, 'pages' => $pages, 'total' => $total, 'per_page' => self::PER_PAGE];

        $items = array_map([self::class, 'present'], UserAdmin::search($filters, self::PER_PAGE, ($page - 1) * self::PER_PAGE));
        return ['items' => $items, 'filters' => $filters, 'pager' => $pager];
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        $row = UserAdmin::findById($id);
        return $row === null ? null : self::present($row);
    }

    /** @param array<string,mixed> $input */
    public static function create(array $input): array
    {
        [$data, $classIds, $errors] = self::sanitize($input, null);
        if ($errors !== []) {
            return self::fail(422, 'Vui lòng kiểm tra lại thông tin.', $errors);
        }

        $id = Database::transaction(static function () use ($data, $classIds): int {
            $newId = UserAdmin::add($data);
            TeacherClass::replace($newId, $classIds);
            return $newId;
        });

        Logger::activity('create_user', 'user', 'Tạo tài khoản: ' . $data['username'], 'success', null, 'user', $id);
        return self::done(201, 'Đã tạo tài khoản.', self::find($id));
    }

    /** @param array<string,mixed> $input */
    public static function update(int $id, array $input): array
    {
        $existing = UserAdmin::findById($id);
        if ($existing === null) {
            return self::fail(404, 'Không tìm thấy tài khoản.');
        }

        [$data, $classIds, $errors] = self::sanitize($input, $existing);
        if ($errors !== []) {
            return self::fail(422, 'Vui lòng kiểm tra lại thông tin.', $errors);
        }

        Database::transaction(static function () use ($id, $data, $classIds): void {
            UserAdmin::change($id, $data);
            TeacherClass::replace($id, $classIds);
        });

        Logger::activity('update_user', 'user', 'Cập nhật tài khoản: ' . $existing['username'], 'success', null, 'user', $id);
        return self::done(200, 'Đã cập nhật tài khoản.', self::find($id));
    }

    /** Khóa hoặc mở khóa tài khoản. */
    public static function setStatus(int $id, string $status): array
    {
        if (!in_array($status, ['active', 'locked'], true)) {
            return self::fail(422, 'Trạng thái không hợp lệ.', ['status' => 'Trạng thái không hợp lệ.']);
        }
        $existing = UserAdmin::findById($id);
        if ($existing === null) {
            return self::fail(404, 'Không tìm thấy tài khoản.');
        }

        if ($status === 'locked') {
            if ($id === Auth::id()) {
                return self::fail(409, 'Bạn không thể tự khóa tài khoản của chính mình.');
            }
            if ($existing['role_code'] === 'ADMIN' && UserAdmin::countActiveAdmins($id) === 0) {
                return self::fail(409, 'Phải còn ít nhất một Admin đang hoạt động.');
            }
        }

        UserAdmin::change($id, ['status' => $status]);
        Logger::activity('status_user', 'user',
            ($status === 'locked' ? 'Khóa tài khoản: ' : 'Mở khóa tài khoản: ') . $existing['username'],
            'success', null, 'user', $id);
        return self::done(200, $status === 'locked' ? 'Đã khóa tài khoản.' : 'Đã mở khóa tài khoản.', self::find($id));
    }

    /**
     * Làm sạch và kiểm tra dữ liệu. $existing = null khi tạo mới.
     * @param array<string,mixed> $input
     * @param array<string,mixed>|null $existing
     * @return array{0:array<string,mixed>,1:int[],2:array<string,string>} [dữ liệu bảng users, lớp dạy, lỗi]
     */
    private static function sanitize(array $input, ?array $existing): array
    {
        $isNew = $existing === null;
        $text  = static fn (string $key): string => isset($input[$key]) && is_scalar($input[$key]) ? trim((string) $input[$key]) : '';

        $strings = [
            'full_name' => $text('full_name'),
            'email'     => $text('email'),
            'phone'     => $text('phone'),
        ];
        $rules  = ['full_name' => 'required|max:150', 'email' => 'required|email|max:150', 'phone' => 'max:20'];
        $labels = ['full_name' => 'Họ và tên', 'email' => 'Email', 'phone' => 'Số điện thoại'];

        if ($isNew) {
            $strings['username'] = $text('username');
            $strings['password'] = isset($input['password']) && is_scalar($input['password']) ? (string) $input['password'] : '';
            $rules['username']   = 'required|min:3|max:50';
            $rules['password']   = 'required|min:8|max:72';
            $labels['username']  = 'Tên đăng nhập';
            $labels['password']  = 'Mật khẩu';
        }

        $errors = Validator::validate($strings, $rules, $labels);
        $exceptId = $isNew ? null : (int) $existing['id'];

        if ($isNew && !isset($errors['username'])) {
            if (preg_match('/^[A-Za-z0-9._-]+$/', $strings['username']) !== 1) {
                $errors['username'] = 'Tên đăng nhập chỉ gồm chữ không dấu, số và các dấu . _ -';
            } elseif (UserAdmin::usernameExists($strings['username'], null)) {
                $errors['username'] = 'Tên đăng nhập này đã tồn tại.';
            }
        }
        if (!isset($errors['email']) && UserAdmin::emailExists($strings['email'], $exceptId)) {
            $errors['email'] = 'Email này đang được dùng cho tài khoản khác.';
        }
        if (!isset($errors['phone']) && $strings['phone'] !== '' && preg_match('/^[0-9+\s().-]{8,20}$/', $strings['phone']) !== 1) {
            $errors['phone'] = 'Số điện thoại không hợp lệ.';
        }

        // Vai trò
        $roleRaw = $text('role_id');
        $role    = ctype_digit($roleRaw) && (int) $roleRaw > 0 ? Role::findById((int) $roleRaw) : null;
        if ($role === null) {
            $errors['role_id'] = 'Hãy chọn vai trò hợp lệ.';
        } elseif (!$isNew) {
            if ((int) $existing['id'] === Auth::id() && (int) $role['id'] !== (int) $existing['role_id']) {
                $errors['role_id'] = 'Bạn không thể đổi vai trò của chính mình.';
            } elseif ($existing['role_code'] === 'ADMIN' && $role['code'] !== 'ADMIN' && UserAdmin::countActiveAdmins((int) $existing['id']) === 0) {
                $errors['role_id'] = 'Phải còn ít nhất một Admin đang hoạt động.';
            }
        }

        // Lớp dạy: chỉ áp dụng cho Tổ trưởng và Giáo viên, các vai trò khác tự xóa
        $classIds = [];
        if ($role !== null && in_array($role['code'], self::TEACHING_ROLES, true)) {
            $raw = $input['class_ids'] ?? [];
            $raw = is_array($raw) ? $raw : [];
            $wanted = [];
            foreach ($raw as $value) {
                if (is_scalar($value) && ctype_digit((string) $value) && (int) $value > 0) {
                    $wanted[(int) $value] = (int) $value;
                }
            }
            $classIds = TeacherClass::existingClassIds(array_values($wanted));
            if (count($classIds) !== count($wanted)) {
                $errors['class_ids'] = 'Có lớp không tồn tại trong danh sách.';
            }
        }

        $data = [
            'full_name' => $strings['full_name'],
            'email'     => $strings['email'],
            'phone'     => $strings['phone'] === '' ? null : $strings['phone'],
            'role_id'   => $role === null ? 0 : (int) $role['id'],
        ];
        if ($isNew && $errors === []) {
            $data['username']      = $strings['username'];
            $data['password_hash'] = password_hash($strings['password'], PASSWORD_DEFAULT);
        }
        return [$data, $classIds, $errors];
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private static function present(array $row): array
    {
        $ids = $row['class_id_list'] === null || $row['class_id_list'] === ''
            ? [] : array_map('intval', explode(',', (string) $row['class_id_list']));

        return [
            'id'            => (int) $row['id'],
            'username'      => $row['username'],
            'full_name'     => $row['full_name'],
            'email'         => $row['email'],
            'phone'         => $row['phone'],
            'role_id'       => (int) $row['role_id'],
            'role_code'     => $row['role_code'],
            'role_name'     => $row['role_name'],
            'status'        => $row['status'],
            'last_login_at' => $row['last_login_at'],
            'class_ids'     => $ids,
            'class_codes'   => (string) $row['class_codes'],
        ];
    }

    private static function fail(int $status, string $message, array $errors = []): array
    {
        return ['ok' => false, 'status' => $status, 'message' => $message, 'errors' => $errors, 'item' => null];
    }

    private static function done(int $status, string $message, mixed $item): array
    {
        return ['ok' => true, 'status' => $status, 'message' => $message, 'errors' => [], 'item' => $item];
    }
}
