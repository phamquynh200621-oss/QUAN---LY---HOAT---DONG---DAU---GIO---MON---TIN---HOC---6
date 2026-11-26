<?php
/** Model BackupLog (bảng backup_logs): chỉ đọc. module-backup là nơi ghi vào bảng này. */
final class BackupLog extends Model
{
    /**
     * @param array{from:?string,to:?string,operation:?string,status:?string} $f
     * @return array<int,array<string,mixed>>
     */
    public static function search(array $f, int $limit, int $offset): array
    {
        [$where, $params] = self::where($f);
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        return self::all(
            "SELECT b.id, b.operation, b.status, b.started_at, b.finished_at, b.file_size, b.message,
                    k.file_name, u.full_name
               FROM backup_logs b
               LEFT JOIN backups k ON k.id = b.backup_id
               LEFT JOIN users u   ON u.id = b.performed_by
               {$where}
              ORDER BY b.started_at DESC, b.id DESC
              LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    /** @param array{from:?string,to:?string,operation:?string,status:?string} $f */
    public static function count(array $f): int
    {
        [$where, $params] = self::where($f);
        $row = self::one("SELECT COUNT(*) AS total FROM backup_logs b {$where}", $params);
        return (int) ($row['total'] ?? 0);
    }

    /** @return array{0:string,1:array<int,mixed>} */
    private static function where(array $f): array
    {
        $sql = [];
        $params = [];
        if ($f['from'] !== null)      { $sql[] = 'b.started_at >= ?'; $params[] = $f['from'] . ' 00:00:00'; }
        if ($f['to'] !== null)        { $sql[] = 'b.started_at <= ?'; $params[] = $f['to'] . ' 23:59:59'; }
        if ($f['operation'] !== null) { $sql[] = 'b.operation = ?';   $params[] = $f['operation']; }
        if ($f['status'] !== null)    { $sql[] = 'b.status = ?';      $params[] = $f['status']; }
        return [$sql === [] ? '' : 'WHERE ' . implode(' AND ', $sql), $params];
    }
}
