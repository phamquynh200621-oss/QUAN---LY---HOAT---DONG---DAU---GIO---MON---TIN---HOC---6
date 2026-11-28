<?php
/**
 * Model ClassSession (bảng class_sessions): một lần tổ chức hoạt động cho một lớp.
 * Thời gian: started_at là lúc bắt đầu/tiếp tục lần chạy hiện tại, elapsed_seconds là số giây đã tích lũy trước đó.
 * Số giây đã trôi tính ngay trong SQL (TIMESTAMPDIFF với NOW()) để không lệch múi giờ giữa PHP và MySQL.
 */
final class ClassSession extends Model
{
    private const COLUMNS = "s.id, s.activity_id, s.teacher_id, s.class_id, s.duration_minutes, s.question_count, s.play_mode,
        s.config_json, s.status, s.created_at, s.ended_at,
        IF(s.status = 'running', s.elapsed_seconds + TIMESTAMPDIFF(SECOND, s.started_at, NOW()), s.elapsed_seconds) AS elapsed_now,
        a.title AS activity_title, y.name AS type_name, c.code AS class_code, c.name AS class_name";

    private const JOINS = 'FROM class_sessions s
        JOIN activities a ON a.id = s.activity_id
        JOIN activity_types y ON y.id = a.type_id
        LEFT JOIN classes c ON c.id = s.class_id';

    /** @return array<string,mixed>|null */
    public static function findById(int $id): ?array
    {
        return self::one('SELECT ' . self::COLUMNS . ' ' . self::JOINS . ' WHERE s.id = ? LIMIT 1', [$id]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function forTeacher(int $teacherId, int $limit, int $offset): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);
        return self::all(
            'SELECT ' . self::COLUMNS . ' ' . self::JOINS . " WHERE s.teacher_id = ?
              ORDER BY s.created_at DESC, s.id DESC LIMIT {$limit} OFFSET {$offset}",
            [$teacherId]
        );
    }

    public static function countForTeacher(int $teacherId): int
    {
        $row = self::one('SELECT COUNT(*) AS total FROM class_sessions WHERE teacher_id = ?', [$teacherId]);
        return (int) ($row['total'] ?? 0);
    }

    public static function create(int $activityId, int $teacherId, string $configJson): int
    {
        return self::insert(
            "INSERT INTO class_sessions (activity_id, teacher_id, status, config_json) VALUES (?, ?, 'selected', ?)",
            [$activityId, $teacherId, $configJson]
        );
    }

    public static function saveConfig(int $id, int $classId, int $duration, int $questionCount, ?string $playMode): void
    {
        self::run(
            "UPDATE class_sessions SET class_id = ?, duration_minutes = ?, question_count = ?, play_mode = ?, status = 'configured'
              WHERE id = ? AND status IN ('selected', 'configured')",
            [$classId, $duration, $questionCount, $playMode, $id]
        );
    }

    public static function saveJson(int $id, string $configJson): void
    {
        self::run('UPDATE class_sessions SET config_json = ? WHERE id = ?', [$configJson, $id]);
    }

    /** Mỗi hàm chuyển trạng thái trả về số dòng đổi (1 = thành công, 0 = trạng thái hiện tại không cho phép). */
    public static function start(int $id): int
    {
        return self::run(
            "UPDATE class_sessions SET status = 'running', started_at = NOW(), elapsed_seconds = 0, ended_at = NULL
              WHERE id = ? AND status = 'configured'",
            [$id]
        );
    }

    public static function pause(int $id): int
    {
        return self::run(
            "UPDATE class_sessions
                SET elapsed_seconds = elapsed_seconds + TIMESTAMPDIFF(SECOND, started_at, NOW()), started_at = NULL, status = 'paused'
              WHERE id = ? AND status = 'running'",
            [$id]
        );
    }

    public static function resume(int $id): int
    {
        return self::run("UPDATE class_sessions SET status = 'running', started_at = NOW() WHERE id = ? AND status = 'paused'", [$id]);
    }

    public static function finish(int $id): int
    {
        return self::run(
            "UPDATE class_sessions
                SET elapsed_seconds = IF(status = 'running', elapsed_seconds + TIMESTAMPDIFF(SECOND, started_at, NOW()), elapsed_seconds),
                    started_at = NULL, status = 'finished', ended_at = NOW()
              WHERE id = ? AND status IN ('running', 'paused')",
            [$id]
        );
    }

    /** Lớp đang hoạt động mà người dùng được phân công dạy. @return array<int,array<string,mixed>> */
    public static function teacherClasses(int $userId): array
    {
        return self::all(
            "SELECT c.id, c.code, c.name FROM classes c JOIN teacher_classes tc ON tc.class_id = c.id
              WHERE tc.teacher_id = ? AND c.status = 'active' ORDER BY c.code",
            [$userId]
        );
    }
}
