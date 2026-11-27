<?php
/** Model Role: vai trò, số người dùng mỗi vai trò và ma trận phân quyền (chỉ đọc). */
final class Role extends Model
{
    /** @return array<int,array<string,mixed>> */
    public static function listAll(): array
    {
        return self::all('SELECT id, code, name FROM roles ORDER BY id');
    }

    /** @return array<string,mixed>|null */
    public static function findById(int $id): ?array
    {
        return self::one('SELECT id, code, name FROM roles WHERE id = ? LIMIT 1', [$id]);
    }

    /** Vai trò kèm số tài khoản đang dùng. @return array<int,array<string,mixed>> */
    public static function withUserCounts(): array
    {
        return self::all(
            'SELECT r.id, r.code, r.name, COUNT(u.id) AS user_count
               FROM roles r LEFT JOIN users u ON u.role_id = r.id
              GROUP BY r.id, r.code, r.name ORDER BY r.id'
        );
    }

    /**
     * Ma trận phân quyền: mỗi chức năng kèm mức quyền (level, scope) của từng vai trò.
     * @return array{roles:array<int,array<string,mixed>>,functions:array<int,array<string,mixed>>}
     */
    public static function matrix(): array
    {
        $roles     = self::listAll();
        $functions = self::all('SELECT code, name, group_name FROM functions ORDER BY code');

        $cells = [];
        foreach (self::all('SELECT role_id, function_code, level, scope FROM role_permissions') as $row) {
            $cells[(int) $row['function_code']][(int) $row['role_id']] = ['level' => $row['level'], 'scope' => $row['scope']];
        }

        foreach ($functions as &$function) {
            $function['code']  = (int) $function['code'];
            $function['cells'] = $cells[$function['code']] ?? [];
        }
        unset($function);

        return ['roles' => $roles, 'functions' => $functions];
    }
}
