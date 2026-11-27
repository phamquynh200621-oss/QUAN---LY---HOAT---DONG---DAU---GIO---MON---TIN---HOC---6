<?php
/** Model TeacherClass (bảng teacher_classes): lớp mà giáo viên/tổ trưởng được phân công dạy. */
final class TeacherClass extends Model
{
    /** Mọi lớp, dùng cho ô chọn lớp dạy. @return array<int,array<string,mixed>> */
    public static function allClasses(): array
    {
        return self::all('SELECT id, code, name, status FROM classes ORDER BY code');
    }

    /** Chỉ giữ lại các mã lớp có thật. @param int[] $ids @return int[] */
    public static function existingClassIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $rows  = self::all("SELECT id FROM classes WHERE id IN ({$marks})", array_values($ids));
        return array_map('intval', array_column($rows, 'id'));
    }

    /** Thay toàn bộ lớp dạy của một tài khoản (gọi trong giao dịch). @param int[] $classIds */
    public static function replace(int $teacherId, array $classIds): void
    {
        self::run('DELETE FROM teacher_classes WHERE teacher_id = ?', [$teacherId]);
        foreach ($classIds as $classId) {
            self::insert('INSERT INTO teacher_classes (teacher_id, class_id) VALUES (?, ?)', [$teacherId, $classId]);
        }
    }
}
