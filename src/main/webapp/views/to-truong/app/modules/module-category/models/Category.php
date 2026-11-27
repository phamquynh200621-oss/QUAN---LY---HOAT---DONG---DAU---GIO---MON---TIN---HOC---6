<?php
/**
 * Model dùng chung cho 4 danh mục: tên bảng/cột lấy từ CategoryDefinition (hằng số trong mã nguồn),
 * mọi giá trị người dùng đều đi qua tham số ? của PDO.
 * Lưu ý: không đặt tên trùng các hàm của lớp Model (one, all, run, insert).
 */
final class Category extends Model
{
    /**
     * @param array<string,mixed> $def
     * @param array<string,int>   $filters cột => giá trị (chỉ các cột nằm trong $def['filters'])
     * @return array<int,array<string,mixed>>
     */
    public static function listAll(array $def, array $filters = []): array
    {
        $where  = [];
        $params = [];
        foreach ($def['filters'] as $column) {
            if (isset($filters[$column])) {
                $where[]  = 't.' . $column . ' = ?';
                $params[] = (int) $filters[$column];
            }
        }
        $sqlWhere = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        return self::all(
            "SELECT {$def['select']} FROM {$def['table']} t {$def['join']} {$sqlWhere} ORDER BY {$def['order']}",
            $params
        );
    }

    /** @return array<string,mixed>|null */
    public static function findById(array $def, int $id): ?array
    {
        return self::one("SELECT {$def['select']} FROM {$def['table']} t {$def['join']} WHERE t.id = ? LIMIT 1", [$id]);
    }

    /** Danh sách (id, name, status) để đổ vào ô chọn. @return array<int,array<string,mixed>> */
    public static function options(array $def): array
    {
        return self::all("SELECT t.id, t.name, t.status FROM {$def['table']} t ORDER BY {$def['order']}");
    }

    /** @param array<string,mixed> $data cột => giá trị (đã kiểm tra) */
    public static function add(array $def, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $marks   = implode(', ', array_fill(0, count($data), '?'));
        return self::insert("INSERT INTO {$def['table']} ({$columns}) VALUES ({$marks})", array_values($data));
    }

    /** @param array<string,mixed> $data */
    public static function change(array $def, int $id, array $data): void
    {
        $set = implode(', ', array_map(static fn (string $c): string => $c . ' = ?', array_keys($data)));
        self::run("UPDATE {$def['table']} SET {$set} WHERE id = ?", array_merge(array_values($data), [$id]));
    }

    public static function remove(array $def, int $id): void
    {
        self::run("DELETE FROM {$def['table']} WHERE id = ?", [$id]);
    }

    /** Còn bản ghi nào ở bảng khác tham chiếu tới danh mục này không. */
    public static function inUse(array $def, int $id): bool
    {
        foreach ($def['usage'] as [$table, $column]) {
            if (self::one("SELECT 1 AS used FROM {$table} WHERE {$column} = ? LIMIT 1", [$id]) !== null) {
                return true;
            }
        }
        return false;
    }

    /** Giá trị đã có trong cột chưa (loại trừ bản ghi $exceptId khi sửa). */
    public static function valueExists(string $table, string $column, mixed $value, ?int $exceptId): bool
    {
        $sql    = "SELECT id FROM {$table} WHERE {$column} = ?";
        $params = [$value];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        return self::one($sql . ' LIMIT 1', $params) !== null;
    }
}
