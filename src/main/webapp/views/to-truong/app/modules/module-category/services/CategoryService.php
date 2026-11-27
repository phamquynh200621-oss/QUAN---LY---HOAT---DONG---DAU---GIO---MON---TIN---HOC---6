<?php
/**
 * Nghiệp vụ danh mục dùng chung.
 *
 * Thêm / sửa: kiểm tra trường bắt buộc, độ dài, số hợp lệ, không trùng mã/tên, chủ đề tồn tại (với bài học).
 * Xóa: không cho xóa khi còn dữ liệu liên quan (lớp có học sinh/kết quả, chủ đề có bài học, bài học/hình thức
 *      đã có hoạt động...). Khi đó hướng người dùng chuyển sang "ngừng sử dụng".
 * Mọi thao tác thành công đều ghi nhật ký hệ thống.
 *
 * Kết quả trả về: ['ok'=>bool, 'status'=>int, 'message'=>string, 'errors'=>array, 'item'=>?array]
 */
final class CategoryService
{
    /** @param array<string,mixed> $def @param array<string,mixed> $input */
    public static function create(array $def, array $input): array
    {
        [$data, $errors] = self::sanitize($def, $input, null);
        if ($errors !== []) {
            return self::fail(422, 'Vui lòng kiểm tra lại thông tin.', $errors);
        }

        $id = Category::add($def, $data);
        Logger::activity('create_category', 'category', 'Thêm ' . $def['singular'] . ': ' . self::label($data), 'success', null, $def['key'], $id);
        return self::done(201, 'Đã thêm ' . $def['singular'] . '.', Category::findById($def, $id));
    }

    public static function update(array $def, int $id, array $input): array
    {
        if (Category::findById($def, $id) === null) {
            return self::fail(404, 'Không tìm thấy ' . $def['singular'] . '.');
        }
        [$data, $errors] = self::sanitize($def, $input, $id);
        if ($errors !== []) {
            return self::fail(422, 'Vui lòng kiểm tra lại thông tin.', $errors);
        }

        Category::change($def, $id, $data);
        Logger::activity('update_category', 'category', 'Sửa ' . $def['singular'] . ': ' . self::label($data), 'success', null, $def['key'], $id);
        return self::done(200, 'Đã cập nhật ' . $def['singular'] . '.', Category::findById($def, $id));
    }

    /** Chuyển giữa "hoạt động" và "ngừng sử dụng". */
    public static function setStatus(array $def, int $id, string $status): array
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            return self::fail(422, 'Trạng thái không hợp lệ.', ['status' => 'Trạng thái không hợp lệ.']);
        }
        $item = Category::findById($def, $id);
        if ($item === null) {
            return self::fail(404, 'Không tìm thấy ' . $def['singular'] . '.');
        }

        Category::change($def, $id, ['status' => $status]);
        Logger::activity('status_category', 'category',
            ($status === 'active' ? 'Kích hoạt ' : 'Ngừng sử dụng ') . $def['singular'] . ': ' . self::label($item),
            'success', null, $def['key'], $id);
        return self::done(200, $status === 'active' ? 'Đã kích hoạt lại.' : 'Đã chuyển sang ngừng sử dụng.', Category::findById($def, $id));
    }

    public static function delete(array $def, int $id): array
    {
        $item = Category::findById($def, $id);
        if ($item === null) {
            return self::fail(404, 'Không tìm thấy ' . $def['singular'] . '.');
        }

        $blocked = 'Không thể xóa ' . $def['singular'] . ' này vì đang có dữ liệu liên quan. Hãy chuyển sang «ngừng sử dụng».';
        if (Category::inUse($def, $id)) {
            return self::fail(409, $blocked);
        }

        try {
            Category::remove($def, $id);
        } catch (PDOException $e) {
            return self::fail(409, $blocked);   // vướng khóa ngoại phát sinh đồng thời
        }

        Logger::activity('delete_category', 'category', 'Xóa ' . $def['singular'] . ': ' . self::label($item), 'success', null, $def['key'], $id);
        return self::done(200, 'Đã xóa ' . $def['singular'] . '.', null);
    }

    /**
     * Làm sạch và kiểm tra dữ liệu theo định nghĩa trường.
     * @return array{0:array<string,mixed>,1:array<string,string>} [dữ liệu hợp lệ, lỗi theo trường]
     */
    private static function sanitize(array $def, array $input, ?int $exceptId): array
    {
        $strings = [];
        $rules   = [];
        $labels  = [];
        foreach ($def['fields'] as $name => $field) {
            $raw             = $input[$name] ?? '';
            $strings[$name]  = is_scalar($raw) ? trim((string) $raw) : '';
            $labels[$name]   = $field['label'];
            if (isset($field['rules'])) {
                $rules[$name] = $field['rules'];
            }
        }

        $errors = Validator::validate($strings, $rules, $labels);
        $data   = [];

        foreach ($def['fields'] as $name => $field) {
            if (isset($errors[$name])) {
                continue;
            }
            $value = $strings[$name];

            switch ($field['type']) {
                case 'number':
                    if ($value === '') {
                        $value = (string) ($field['default'] ?? 0);
                    }
                    $number = preg_match('/^\d+$/', $value) === 1 ? (int) $value : -1;
                    if ($number < $field['min'] || $number > $field['max']) {
                        $errors[$name] = "{$field['label']} phải là số nguyên từ {$field['min']} đến {$field['max']}.";
                        break;
                    }
                    $data[$name] = $number;
                    break;

                case 'select':
                    $ref = CategoryDefinition::get($field['source']);
                    if (preg_match('/^\d+$/', $value) !== 1 || (int) $value < 1
                        || !Category::valueExists($ref['table'], 'id', (int) $value, null)) {
                        $errors[$name] = "{$field['label']} không hợp lệ.";
                        break;
                    }
                    $data[$name] = (int) $value;
                    break;

                default:
                    $data[$name] = ($value === '' && !empty($field['nullable'])) ? null : $value;
            }

            if (!isset($errors[$name]) && !empty($field['unique'])
                && Category::valueExists($def['table'], $name, $data[$name], $exceptId)) {
                $errors[$name] = "{$field['label']} này đã tồn tại.";
            }
        }
        return [$data, $errors];
    }

    /** Tên ngắn của bản ghi để ghi nhật ký. */
    private static function label(array $row): string
    {
        return (string) ($row['name'] ?? $row['code'] ?? '');
    }

    private static function fail(int $status, string $message, array $errors = []): array
    {
        return ['ok' => false, 'status' => $status, 'message' => $message, 'errors' => $errors, 'item' => null];
    }

    private static function done(int $status, string $message, ?array $item): array
    {
        return ['ok' => true, 'status' => $status, 'message' => $message, 'errors' => [], 'item' => $item];
    }
}
