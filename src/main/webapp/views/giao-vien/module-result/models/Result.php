<?php
/** Model Result: bảng results và result_histories. Chỉ chứa câu lệnh SQL; nghiệp vụ nằm ở ResultService. */
final class Result extends Model
{
    /**
     * Học sinh của lớp kèm kết quả (nếu có) trong một phiên.
     * @return array<int,array<string,mixed>>
     */
    public static function sheet(int $sessionId, int $classId): array
    {
        return self::all(
            "SELECT s.id AS student_id, s.student_code, s.full_name,
                    r.id AS result_id, r.score, r.rating_level, r.comment, r.save_status
               FROM students s
               LEFT JOIN results r ON r.student_id = s.id AND r.session_id = ?
              WHERE s.class_id = ? AND (s.status = 'active' OR r.id IS NOT NULL)
              ORDER BY s.full_name, s.student_code",
            [$sessionId, $classId]
        );
    }

    /** Mã học sinh => trạng thái của cả lớp (để kiểm tra học sinh có thuộc lớp). @return array<int,string> */
    public static function studentStatuses(int $classId): array
    {
        $map = [];
        foreach (self::all('SELECT id, status FROM students WHERE class_id = ?', [$classId]) as $row) {
            $map[(int) $row['id']] = $row['status'];
        }
        return $map;
    }

    /** @return array<string,mixed>|null */
    public static function findForSession(int $sessionId, int $studentId): ?array
    {
        return self::one(
            'SELECT id, score, rating_level, comment, save_status FROM results WHERE session_id = ? AND student_id = ? LIMIT 1',
            [$sessionId, $studentId]
        );
    }

    public static function add(int $sessionId, int $studentId, int $activityId, int $classId, ?float $score, ?string $rating, ?string $comment, int $userId): int
    {
        return self::insert(
            "INSERT INTO results (session_id, student_id, activity_id, class_id, score, rating_level, comment, save_status, recorded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', ?)",
            [$sessionId, $studentId, $activityId, $classId, $score, $rating, $comment, $userId]
        );
    }

    /** Sửa kết quả và đưa về nháp cho tới khi lưu chính thức lại. */
    public static function change(int $id, ?float $score, ?string $rating, ?string $comment, int $userId): void
    {
        self::run(
            "UPDATE results SET score = ?, rating_level = ?, comment = ?, save_status = 'draft', recorded_by = ? WHERE id = ?",
            [$score, $rating, $comment, $userId, $id]
        );
    }

    public static function addHistory(int $resultId, ?float $score, ?string $rating, ?string $comment, int $userId): void
    {
        self::insert(
            'INSERT INTO result_histories (result_id, score, rating_level, comment, changed_by) VALUES (?, ?, ?, ?, ?)',
            [$resultId, $score, $rating, $comment, $userId]
        );
    }

    /** @return array{draft:int,saved:int,failed:int} */
    public static function counts(int $sessionId): array
    {
        $counts = ['draft' => 0, 'saved' => 0, 'failed' => 0];
        foreach (self::all('SELECT save_status, COUNT(*) AS total FROM results WHERE session_id = ? GROUP BY save_status', [$sessionId]) as $row) {
            $counts[$row['save_status']] = (int) $row['total'];
        }
        return $counts;
    }

    /** Lưu chính thức mọi kết quả chưa lưu của phiên; trả về số dòng đổi. */
    public static function markSaved(int $sessionId): int
    {
        return self::run("UPDATE results SET save_status = 'saved' WHERE session_id = ? AND save_status <> 'saved'", [$sessionId]);
    }

    // ---- Lịch sử ---------------------------------------------------------

    private const JOINS = 'FROM results r
        JOIN students st ON st.id = r.student_id
        JOIN classes c ON c.id = r.class_id
        JOIN activities a ON a.id = r.activity_id
        JOIN class_sessions cs ON cs.id = r.session_id
        JOIN users u ON u.id = r.recorded_by';

    /**
     * @param array{q:?string,class_id:?int,activity_id:?int,from:?string,to:?string} $f
     * @param int[]|null $allowedClassIds null = mọi lớp
     * @return array<int,array<string,mixed>>
     */
    public static function search(array $f, ?array $allowedClassIds, int $limit, int $offset): array
    {
        [$where, $params] = self::where($f, $allowedClassIds);
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        return self::all(
            'SELECT r.id, r.session_id, r.score, r.rating_level, r.comment, r.save_status, r.updated_at,
                    st.student_code, st.full_name AS student_name, c.code AS class_code, a.title AS activity_title,
                    u.full_name AS recorder_name, cs.teacher_id '
            . self::JOINS . " {$where} ORDER BY r.updated_at DESC, r.id DESC LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    /** @param array<string,mixed> $f @param int[]|null $allowedClassIds */
    public static function count(array $f, ?array $allowedClassIds): int
    {
        [$where, $params] = self::where($f, $allowedClassIds);
        $row = self::one('SELECT COUNT(*) AS total ' . self::JOINS . " {$where}", $params);
        return (int) ($row['total'] ?? 0);
    }

    /** Hoạt động đã có kết quả (cho ô lọc). @param int[]|null $allowedClassIds @return array<int,array<string,mixed>> */
    public static function activityOptions(?array $allowedClassIds): array
    {
        if ($allowedClassIds !== null && $allowedClassIds === []) {
            return [];
        }
        $where  = '';
        $params = [];
        if ($allowedClassIds !== null) {
            $where  = 'WHERE r.class_id IN (' . implode(',', array_fill(0, count($allowedClassIds), '?')) . ')';
            $params = $allowedClassIds;
        }
        return self::all("SELECT DISTINCT a.id, a.title FROM results r JOIN activities a ON a.id = r.activity_id {$where} ORDER BY a.title", $params);
    }

    /** @return array<string,mixed>|null */
    public static function findById(int $id): ?array
    {
        return self::one(
            'SELECT r.id, r.class_id, r.session_id, st.student_code, st.full_name AS student_name, a.title AS activity_title
               FROM results r JOIN students st ON st.id = r.student_id JOIN activities a ON a.id = r.activity_id
              WHERE r.id = ? LIMIT 1',
            [$id]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function history(int $resultId): array
    {
        return self::all(
            'SELECT h.id, h.score, h.rating_level, h.comment, h.changed_at, u.full_name AS changed_by_name
               FROM result_histories h JOIN users u ON u.id = h.changed_by
              WHERE h.result_id = ? ORDER BY h.changed_at DESC, h.id DESC',
            [$resultId]
        );
    }

    /** @param array<string,mixed> $f @param int[]|null $allowedClassIds @return array{0:string,1:array<int,mixed>} */
    private static function where(array $f, ?array $allowedClassIds): array
    {
        if ($allowedClassIds !== null && $allowedClassIds === []) {
            return ['WHERE 1 = 0', []];
        }
        $sql    = [];
        $params = [];
        if ($allowedClassIds !== null) {
            $sql[] = 'r.class_id IN (' . implode(',', array_fill(0, count($allowedClassIds), '?')) . ')';
            array_push($params, ...$allowedClassIds);
        }
        if ($f['class_id'] !== null)    { $sql[] = 'r.class_id = ?';    $params[] = $f['class_id']; }
        if ($f['activity_id'] !== null) { $sql[] = 'r.activity_id = ?'; $params[] = $f['activity_id']; }
        if ($f['from'] !== null)        { $sql[] = 'r.updated_at >= ?'; $params[] = $f['from'] . ' 00:00:00'; }
        if ($f['to'] !== null)          { $sql[] = 'r.updated_at <= ?'; $params[] = $f['to'] . ' 23:59:59'; }
        if ($f['q'] !== null) {
            $like     = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $f['q']) . '%';
            $sql[]    = '(st.student_code LIKE ? OR st.full_name LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }
        return [$sql === [] ? '' : 'WHERE ' . implode(' AND ', $sql), $params];
    }
}
