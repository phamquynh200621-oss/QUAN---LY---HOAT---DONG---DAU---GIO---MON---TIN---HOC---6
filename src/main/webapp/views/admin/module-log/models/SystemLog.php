<?php
/** Model SystemLog (bảng system_logs): chỉ đọc, có lọc theo ngày, người dùng, thao tác. */
final class SystemLog extends Model
{
    /**
     * @param array{from:?string,to:?string,user_id:?int,action:?string} $f
     * @return array<int,array<string,mixed>>
     */
    public static function search(array $f, int $limit, int $offset): array
    {
        [$where, $params] = self::where($f);
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        return self::all(
            "SELECT l.id, l.action, l.module, l.target_type, l.target_id, l.detail, l.ip_address, l.status,
                    l.created_at, l.user_id, u.full_name, u.username
               FROM system_logs l LEFT JOIN users u ON u.id = l.user_id
               {$where}
              ORDER BY l.created_at DESC, l.id DESC
              LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    /** @param array{from:?string,to:?string,user_id:?int,action:?string} $f */
    public static function count(array $f): int
    {
        [$where, $params] = self::where($f);
        $row = self::one("SELECT COUNT(*) AS total FROM system_logs l {$where}", $params);
        return (int) ($row['total'] ?? 0);
    }

    /** Người dùng từng có thao tác được ghi lại (cho ô lọc). @return array<int,array<string,mixed>> */
    public static function users(): array
    {
        return self::all(
            'SELECT DISTINCT u.id, u.full_name FROM users u JOIN system_logs l ON l.user_id = u.id ORDER BY u.full_name'
        );
    }

    /** @return string[] */
    public static function actions(): array
    {
        return array_column(self::all('SELECT DISTINCT action FROM system_logs ORDER BY action'), 'action');
    }

    /** @return array{0:string,1:array<int,mixed>} */
    private static function where(array $f): array
    {
        $sql = [];
        $params = [];
        if ($f['from'] !== null)    { $sql[] = 'l.created_at >= ?'; $params[] = $f['from'] . ' 00:00:00'; }
        if ($f['to'] !== null)      { $sql[] = 'l.created_at <= ?'; $params[] = $f['to'] . ' 23:59:59'; }
        if ($f['user_id'] !== null) { $sql[] = 'l.user_id = ?';     $params[] = $f['user_id']; }
        if ($f['action'] !== null)  { $sql[] = 'l.action = ?';      $params[] = $f['action']; }
        return [$sql === [] ? '' : 'WHERE ' . implode(' AND ', $sql), $params];
    }
}
