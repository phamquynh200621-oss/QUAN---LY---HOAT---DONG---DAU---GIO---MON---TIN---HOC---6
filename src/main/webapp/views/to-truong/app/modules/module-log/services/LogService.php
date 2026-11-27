<?php
/**
 * Nghiệp vụ xem nhật ký: chuẩn hóa bộ lọc, phân trang, nhãn tiếng Việt.
 *
 * MDVII2.2 Xem nhật ký
 *   Input : khoảng ngày, người thực hiện / thao tác (nhật ký hệ thống) hoặc loại thao tác, trạng thái (sao lưu/phục hồi)
 *   Process: lọc, sắp theo thời gian mới nhất, phân trang 20 dòng
 *   Output: danh sách người thực hiện, thời điểm, thao tác, trạng thái, chi tiết
 *
 * Khi module khác thêm thao tác mới vào Logger::activity(), thêm nhãn tiếng Việt vào ACTION_LABELS
 * (không thêm thì trang vẫn hiển thị mã thao tác gốc).
 */
final class LogService
{
    public const PER_PAGE = 20;

    private const ACTION_LABELS = [
        'login'           => 'Đăng nhập',
        'logout'          => 'Đăng xuất',
        'forgot_password' => 'Yêu cầu đặt lại mật khẩu',
        'reset_password'  => 'Đặt lại mật khẩu',
        'update_profile'  => 'Cập nhật hồ sơ',
        'change_password' => 'Đổi mật khẩu',
        'update_avatar'   => 'Cập nhật ảnh đại diện',
        'create_category' => 'Thêm danh mục',
        'update_category' => 'Sửa danh mục',
        'status_category' => 'Đổi trạng thái danh mục',
        'delete_category' => 'Xóa danh mục',
        'create_student'  => 'Thêm học sinh',
        'update_student'  => 'Sửa học sinh',
        'status_student'  => 'Đổi trạng thái học sinh',
        'delete_student'  => 'Xóa học sinh',
        'import_students' => 'Nhập danh sách học sinh',
        'create_user'     => 'Tạo tài khoản',
        'update_user'     => 'Cập nhật tài khoản',
        'status_user'     => 'Khóa / mở khóa tài khoản',
    ];

    /**
     * @param array<string,mixed> $input thường là $request->all()
     * @return array{items:array<int,array<string,mixed>>,filters:array<string,mixed>,pager:array<string,int>}
     */
    public static function systemLogs(array $input): array
    {
        $filters = [
            'from'    => self::date($input['from'] ?? ''),
            'to'      => self::date($input['to'] ?? ''),
            'user_id' => self::positiveInt($input['user_id'] ?? ''),
            'action'  => self::token($input['action'] ?? ''),
        ];
        $pager = self::pager(SystemLog::count($filters), $input);

        return [
            'items'   => SystemLog::search($filters, $pager['per_page'], $pager['offset']),
            'filters' => $filters,
            'pager'   => $pager,
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @return array{items:array<int,array<string,mixed>>,filters:array<string,mixed>,pager:array<string,int>}
     */
    public static function backupLogs(array $input): array
    {
        $operation = (string) ($input['operation'] ?? '');
        $status    = (string) ($input['status'] ?? '');

        $filters = [
            'from'      => self::date($input['from'] ?? ''),
            'to'        => self::date($input['to'] ?? ''),
            'operation' => in_array($operation, ['backup', 'restore'], true) ? $operation : null,
            'status'    => in_array($status, ['success', 'failed'], true) ? $status : null,
        ];
        $pager = self::pager(BackupLog::count($filters), $input);

        return [
            'items'   => BackupLog::search($filters, $pager['per_page'], $pager['offset']),
            'filters' => $filters,
            'pager'   => $pager,
        ];
    }

    public static function actionLabel(string $action): string
    {
        return self::ACTION_LABELS[$action] ?? $action;
    }

    public static function formatBytes(?int $bytes): string
    {
        if ($bytes === null) {
            return '';
        }
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        $units = ['KB', 'MB', 'GB'];
        $value = $bytes / 1024;
        $i = 0;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }
        return number_format($value, 1, ',', '.') . ' ' . $units[$i];
    }

    /** @return array<string,int> */
    private static function pager(int $total, array $input): array
    {
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($pages, max(1, (int) ($input['page'] ?? 1)));
        return [
            'page'     => $page,
            'pages'    => $pages,
            'total'    => $total,
            'per_page' => self::PER_PAGE,
            'offset'   => ($page - 1) * self::PER_PAGE,
        ];
    }

    /** Ngày hợp lệ dạng Y-m-d, ngược lại null. */
    private static function date(mixed $value): ?string
    {
        $text = is_scalar($value) ? trim((string) $value) : '';
        $date = DateTime::createFromFormat('Y-m-d', $text);
        return ($date !== false && $date->format('Y-m-d') === $text) ? $text : null;
    }

    private static function positiveInt(mixed $value): ?int
    {
        $text = is_scalar($value) ? trim((string) $value) : '';
        return ($text !== '' && ctype_digit($text) && (int) $text > 0) ? (int) $text : null;
    }

    /** Mã thao tác chỉ gồm chữ thường và gạch dưới. */
    private static function token(mixed $value): ?string
    {
        $text = is_scalar($value) ? trim((string) $value) : '';
        return preg_match('/^[a-z_]{1,50}$/', $text) === 1 ? $text : null;
    }
}
