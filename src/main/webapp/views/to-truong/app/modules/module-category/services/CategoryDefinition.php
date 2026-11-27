<?php
/**
 * Định nghĩa 4 danh mục dùng chung. Mọi thứ đặc thù (bảng, trường, cột hiển thị, ràng buộc xóa)
 * nằm ở đây; controller, service, model và view đều đọc từ định nghĩa này.
 *
 * Trường (fields): type = text | number | select
 *   rules   luật Validator (required|max:n)
 *   unique  không được trùng giá trị trong bảng
 *   nullable chuỗi rỗng lưu thành NULL
 *   min/max/default cho số; source = khóa danh mục tham chiếu cho select
 * usage: các cặp [bảng, cột] tham chiếu tới danh mục này; còn dữ liệu tham chiếu thì không được xóa.
 * Tên bảng và cột chỉ lấy từ file này (không bao giờ từ dữ liệu người dùng gửi lên).
 */
final class CategoryDefinition
{
    /** @return array<string,array<string,mixed>> */
    private static function all(): array
    {
        return [
            'classes' => [
                'title'    => 'Lớp học',
                'singular' => 'lớp học',
                'table'    => 'classes',
                'function' => 4,
                'fields'   => [
                    'code'        => ['label' => 'Mã lớp',  'type' => 'text',   'rules' => 'required|max:20', 'unique' => true],
                    'name'        => ['label' => 'Tên lớp', 'type' => 'text',   'rules' => 'required|max:100'],
                    'grade'       => ['label' => 'Khối',    'type' => 'number', 'min' => 1, 'max' => 12, 'default' => 6],
                    'description' => ['label' => 'Mô tả',   'type' => 'text',   'rules' => 'max:255', 'nullable' => true],
                ],
                'columns'  => ['code' => 'Mã lớp', 'name' => 'Tên lớp', 'grade' => 'Khối', 'description' => 'Mô tả'],
                'order'    => 't.code',
                'usage'    => [['students', 'class_id'], ['teacher_classes', 'class_id'], ['class_sessions', 'class_id'], ['results', 'class_id']],
            ],
            'topics' => [
                'title'    => 'Chủ đề',
                'singular' => 'chủ đề',
                'table'    => 'topics',
                'function' => 5,
                'fields'   => [
                    'name'       => ['label' => 'Tên chủ đề', 'type' => 'text',   'rules' => 'required|max:200'],
                    'sort_order' => ['label' => 'Thứ tự',     'type' => 'number', 'min' => 0, 'max' => 9999, 'default' => 0],
                ],
                'columns'  => ['name' => 'Tên chủ đề', 'sort_order' => 'Thứ tự'],
                'order'    => 't.sort_order, t.name',
                'usage'    => [['lessons', 'topic_id'], ['activities', 'topic_id']],
            ],
            'lessons' => [
                'title'    => 'Bài học',
                'singular' => 'bài học',
                'table'    => 'lessons',
                'function' => 5,
                'fields'   => [
                    'topic_id'   => ['label' => 'Chủ đề',    'type' => 'select', 'source' => 'topics'],
                    'name'       => ['label' => 'Tên bài học', 'type' => 'text',  'rules' => 'required|max:200'],
                    'sort_order' => ['label' => 'Thứ tự',      'type' => 'number', 'min' => 0, 'max' => 9999, 'default' => 0],
                ],
                'columns'  => ['topic_name' => 'Chủ đề', 'name' => 'Tên bài học', 'sort_order' => 'Thứ tự'],
                'select'   => 't.*, p.name AS topic_name',
                'join'     => 'JOIN topics p ON p.id = t.topic_id',
                'order'    => 'p.sort_order, p.name, t.sort_order, t.name',
                'filters'  => ['topic_id'],
                'usage'    => [['activities', 'lesson_id']],
            ],
            'activity-types' => [
                'title'    => 'Hình thức hoạt động',
                'singular' => 'hình thức hoạt động',
                'table'    => 'activity_types',
                'function' => 6,
                'fields'   => [
                    'name'        => ['label' => 'Tên hình thức', 'type' => 'text', 'rules' => 'required|max:100', 'unique' => true],
                    'description' => ['label' => 'Mô tả',          'type' => 'text', 'rules' => 'max:255', 'nullable' => true],
                ],
                'columns'  => ['name' => 'Tên hình thức', 'description' => 'Mô tả'],
                'order'    => 't.name',
                'usage'    => [['activities', 'type_id']],
            ],
        ];
    }

    /** @return array<string,mixed>|null null nếu khóa không tồn tại */
    public static function get(string $key): ?array
    {
        $all = self::all();
        if (!isset($all[$key])) {
            return null;
        }
        return ['key' => $key, 'select' => 't.*', 'join' => '', 'filters' => []] + $all[$key];
    }

    /** Đường dẫn trang giao diện và API của một danh mục. */
    public static function pagePath(array $def): string
    {
        return '/categories/' . $def['key'];
    }

    public static function apiPath(array $def): string
    {
        return '/api/' . $def['key'];
    }
}
