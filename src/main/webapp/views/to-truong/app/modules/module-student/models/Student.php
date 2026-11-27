<?php
/** Model Student (bảng students). Chỉ chứa câu lệnh SQL; quy tắc nghiệp vụ nằm ở StudentService. */
final class Student extends Model
{
    private const SELECT = 's.id, s.student_code, s.full_name, s.dob, s.gender, s.class_id, s.status,
                            c.code AS class_code, c.name AS class_name';

    /**
     * @param array{q:?string,class_id:?int} $f
     * @param int[]|null $allowedClassIds null = không giới hạn lớp
     * @return array<int,array<string,mixed>>
     */
    public static function search(array $f, ?array $allowedClassIds, int $limit, int $offset): array
    {
        [$where, $params] = self::where($f, $allowedClassIds);
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        return self::all(
            'SELECT ' . self::SELECT . " FROM students s JOIN classes c ON c.id = s.class_id {$where}
              ORDER BY c.code, s.full_name LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    /** @param array{q:?string,class_id:?int} $f @param int[]|null $allowedClassIds */
    public static function count(array $f, ?array $allowedClassIds): int
    {
        [$where, $params] = self::where($f, $allowedClassIds);
        $row = self::one("SELECT COUNT(*) AS total FROM students s {$where}", $params);
        return (int) ($row['total'] ?? 0);
    }

    /** @return array<string,mixed>|null */
    public static function findById(int $id): ?array
    {
        return self::one(
            'SELECT ' . self::SELECT . ' FROM students s JOIN classes c ON c.id = s.class_id WHERE s.id = ? LIMIT 1',
            [$id]
        );
    }

    /** @param array<string,mixed> $data cột => giá trị đã kiểm tra */
    public static function add(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $marks   = implode(', ', array_fill(0, count($data), '?'));
        return self::insert("INSERT INTO students ({$columns}) VALUES ({$marks})", array_values($data));
    }

    /** @param array<string,mixed> $data */
    public static function change(int $id, array $data): void
    {
        $set = implode(', ', array_map(static fn (string $c): string => $c . ' = ?', array_keys($data)));
        self::run("UPDATE students SET {$set} WHERE id = ?", array_merge(array_values($data), [$id]));
    }

    public static function remove(int $id): void
    {
        self::run('DELETE FROM students WHERE id = ?', [$id]);
    }

    public static function hasResults(int $id): bool
    {
        return self::one('SELECT 1 AS used FROM results WHERE student_id = ? LIMIT 1', [$id]) !== null;
    }

    public static function codeExists(string $code, ?int $exceptId): bool
    {
        $sql    = 'SELECT id FROM students WHERE student_code = ?';
        $params = [$code];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        return self::one($sql . ' LIMIT 1', $params) !== null;
    }

    /** @return array<string,mixed>|null id, code, name, status của lớp */
    public static function classInfo(int $classId): ?array
    {
        return self::one('SELECT id, code, name, status FROM classes WHERE id = ? LIMIT 1', [$classId]);
    }

    /**
     * Các lớp (cho ô chọn). $allowedClassIds null = tất cả; mảng = chỉ các lớp đó.
     * @param int[]|null $allowedClassIds
     * @return array<int,array<string,mixed>>
     */
    public static function classes(?array $allowedClassIds): array
    {
        if ($allowedClassIds === null) {
            return self::all('SELECT id, code, name, status FROM classes ORDER BY code');
        }
        if ($allowedClassIds === []) {
            return [];
        }
        $marks = implode(',', array_fill(0, count($allowedClassIds), '?'));
        return self::all("SELECT id, code, name, status FROM classes WHERE id IN ({$marks}) ORDER BY code", $allowedClassIds);
    }

    /** Lớp được phân công dạy (bảng teacher_classes do module-user quản lý). @return int[] */
    public static function teacherClassIds(int $userId): array
    {
        $rows = self::all('SELECT class_id FROM teacher_classes WHERE teacher_id = ?', [$userId]);
        return array_map('intval', array_column($rows, 'class_id'));
    }

    /**
     * @param array{q:?string,class_id:?int} $f
     * @param int[]|null $allowedClassIds
     * @return array{0:string,1:array<int,mixed>}
     */
    private static function where(array $f, ?array $allowedClassIds): array
    {
        if ($allowedClassIds !== null && $allowedClassIds === []) {
            return ['WHERE 1 = 0', []];
        }

        $sql    = [];
        $params = [];
        if ($allowedClassIds !== null) {
            $sql[] = 's.class_id IN (' . implode(',', array_fill(0, count($allowedClassIds), '?')) . ')';
            array_push($params, ...$allowedClassIds);
        }
        if ($f['class_id'] !== null) {
            $sql[]    = 's.class_id = ?';
            $params[] = $f['class_id'];
        }
        if ($f['q'] !== null) {
            $like     = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $f['q']) . '%';
            $sql[]    = '(s.student_code LIKE ? OR s.full_name LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }
        return [$sql === [] ? '' : 'WHERE ' . implode(' AND ', $sql), $params];
    }
}
