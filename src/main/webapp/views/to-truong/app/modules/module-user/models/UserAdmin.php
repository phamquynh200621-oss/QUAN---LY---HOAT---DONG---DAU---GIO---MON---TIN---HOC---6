<?php
/** Model UserAdmin: truy vấn bảng users cho việc quản trị tài khoản (module-auth giữ model User cho đăng nhập). */
final class UserAdmin extends Model
{
    private const SELECT = "u.id, u.username, u.email, u.full_name, u.phone, u.role_id, u.status, u.last_login_at,
        r.code AS role_code, r.name AS role_name,
        (SELECT GROUP_CONCAT(c.code ORDER BY c.code SEPARATOR ', ')
           FROM teacher_classes tc JOIN classes c ON c.id = tc.class_id WHERE tc.teacher_id = u.id) AS class_codes,
        (SELECT GROUP_CONCAT(tc.class_id) FROM teacher_classes tc WHERE tc.teacher_id = u.id) AS class_id_list";

    /**
     * @param array{q:?string,role_id:?int,status:?string} $f
     * @return array<int,array<string,mixed>>
     */
    public static function search(array $f, int $limit, int $offset): array
    {
        [$where, $params] = self::where($f);
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        return self::all(
            'SELECT ' . self::SELECT . " FROM users u JOIN roles r ON r.id = u.role_id {$where}
              ORDER BY r.id, u.full_name LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    /** @param array{q:?string,role_id:?int,status:?string} $f */
    public static function count(array $f): int
    {
        [$where, $params] = self::where($f);
        $row = self::one("SELECT COUNT(*) AS total FROM users u {$where}", $params);
        return (int) ($row['total'] ?? 0);
    }

    /** @return array<string,mixed>|null */
    public static function findById(int $id): ?array
    {
        return self::one('SELECT ' . self::SELECT . ' FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? LIMIT 1', [$id]);
    }

    public static function usernameExists(string $username, ?int $exceptId): bool
    {
        return self::exists('username', $username, $exceptId);
    }

    public static function emailExists(string $email, ?int $exceptId): bool
    {
        return self::exists('email', $email, $exceptId);
    }

    /** @param array<string,mixed> $data cột => giá trị đã kiểm tra */
    public static function add(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $marks   = implode(', ', array_fill(0, count($data), '?'));
        return self::insert("INSERT INTO users ({$columns}) VALUES ({$marks})", array_values($data));
    }

    /** @param array<string,mixed> $data */
    public static function change(int $id, array $data): void
    {
        $set = implode(', ', array_map(static fn (string $c): string => $c . ' = ?', array_keys($data)));
        self::run("UPDATE users SET {$set} WHERE id = ?", array_merge(array_values($data), [$id]));
    }

    /** Số Admin đang hoạt động, không tính tài khoản $exceptId. */
    public static function countActiveAdmins(int $exceptId): int
    {
        $row = self::one(
            "SELECT COUNT(*) AS total FROM users u JOIN roles r ON r.id = u.role_id
              WHERE r.code = 'ADMIN' AND u.status = 'active' AND u.id <> ?",
            [$exceptId]
        );
        return (int) ($row['total'] ?? 0);
    }

    private static function exists(string $column, string $value, ?int $exceptId): bool
    {
        $sql    = "SELECT id FROM users WHERE {$column} = ?";
        $params = [$value];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        return self::one($sql . ' LIMIT 1', $params) !== null;
    }

    /** @param array{q:?string,role_id:?int,status:?string} $f @return array{0:string,1:array<int,mixed>} */
    private static function where(array $f): array
    {
        $sql    = [];
        $params = [];
        if ($f['q'] !== null) {
            $like     = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $f['q']) . '%';
            $sql[]    = '(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        if ($f['role_id'] !== null) {
            $sql[]    = 'u.role_id = ?';
            $params[] = $f['role_id'];
        }
        if ($f['status'] !== null) {
            $sql[]    = 'u.status = ?';
            $params[] = $f['status'];
        }
        return [$sql === [] ? '' : 'WHERE ' . implode(' AND ', $sql), $params];
    }
}
