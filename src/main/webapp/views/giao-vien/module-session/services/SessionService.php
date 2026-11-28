<?php
/**
 * Nghiệp vụ tổ chức hoạt động cho lớp (dòng 14, 15).
 *
 * MDV1.1 Xem trước: dựng nội dung hoạt động như lúc trình chiếu, chưa tạo phiên.
 * MDV1.2 Xác nhận chọn: chỉ chọn hoạt động đã duyệt; tạo phiên ở trạng thái 'selected'.
 * MDV2.1 Thiết lập thông số: lớp (trong các lớp mình dạy), thời gian, số câu hỏi, cách chơi -> 'configured'.
 * MDV2.2 Điều khiển: start, pause, resume, end; thêm next/prev/reveal để chuyển câu hỏi và hiện đáp án khi trình chiếu.
 * MDV2.3 Trình chiếu: màn hình chiếu lấy trạng thái phiên bằng cách hỏi lại máy chủ mỗi 2 giây (polling).
 *
 * Trạng thái phiên: selected -> configured -> running <-> paused -> finished.
 * Chỉ người tạo phiên (giáo viên/tổ trưởng) được thiết lập, điều khiển, trình chiếu phiên đó.
 *
 * Kết quả trả về: ['ok'=>bool, 'status'=>int, 'message'=>string, 'errors'=>array, 'item'=>mixed]
 */
final class SessionService
{
    public const PER_PAGE = 20;

    public const STATUS_LABELS = [
        'selected'   => 'Đã chọn hoạt động',
        'configured' => 'Đã thiết lập',
        'running'    => 'Đang diễn ra',
        'paused'     => 'Tạm dừng',
        'finished'   => 'Đã kết thúc',
    ];
    public const STATUS_BADGES = [
        'selected'   => 'text-bg-secondary',
        'configured' => 'text-bg-info',
        'running'    => 'text-bg-success',
        'paused'     => 'text-bg-warning',
        'finished'   => 'text-bg-dark',
    ];

    private const MAX_DURATION = 180;

    // ======================= Chọn và xem trước =======================

    /**
     * Danh sách hoạt động đã duyệt để chọn (dùng lại bộ tìm kiếm của module-search).
     * @param array<string,mixed> $input
     * @return array{items:array,filters:array,pager:array}
     */
    public static function selectable(array $input): array
    {
        return SearchService::listing(array_merge($input, ['status' => 'approved', 'mine' => null]));
    }

    /** Dữ liệu xem trước; null nếu không được xem. @return array<string,mixed>|null */
    public static function preview(int $activityId): ?array
    {
        $activity = ActivityService::find($activityId);
        if ($activity === null) {
            return null;
        }
        $activity['can_select'] = $activity['status'] === 'approved' && Permission::can(14, 'T');
        return $activity;
    }

    public static function create(int $activityId): array
    {
        $activity = ActivityService::find($activityId);
        if ($activity === null) {
            return self::fail(404, 'Không tìm thấy hoạt động.');
        }
        if ($activity['status'] !== 'approved') {
            return self::fail(409, 'Chỉ chọn được hoạt động đã duyệt.');
        }

        $id = ClassSession::create($activityId, (int) Auth::id(), json_encode(['current' => 0, 'reveal' => false]));
        Logger::activity('select_activity', 'session', 'Chọn hoạt động để tổ chức: ' . $activity['title'], 'success', null, 'session', $id);
        return self::done(201, 'Đã chọn hoạt động.', ['id' => $id]);
    }

    // ======================= Phiên của tôi =======================

    /** Phiên thuộc người dùng hiện tại, ngược lại null. @return array<string,mixed>|null */
    public static function own(int $sessionId): ?array
    {
        $session = ClassSession::findById($sessionId);
        return ($session !== null && (int) $session['teacher_id'] === (int) Auth::id()) ? $session : null;
    }

    /** @param array<string,mixed> $input @return array{items:array,pager:array} */
    public static function mine(array $input): array
    {
        $userId = (int) Auth::id();
        $total  = ClassSession::countForTeacher($userId);
        $pages  = max(1, (int) ceil($total / self::PER_PAGE));
        $page   = min($pages, max(1, (int) ($input['page'] ?? 1)));

        $items = array_map(static function (array $s): array {
            $s['id']           = (int) $s['id'];
            $s['status_label'] = self::STATUS_LABELS[$s['status']] ?? $s['status'];
            return $s;
        }, ClassSession::forTeacher($userId, self::PER_PAGE, ($page - 1) * self::PER_PAGE));

        return ['items' => $items, 'pager' => ['page' => $page, 'pages' => $pages, 'total' => $total, 'per_page' => self::PER_PAGE]];
    }

    // ======================= Thiết lập =======================

    /** Số câu hỏi của hoạt động trong phiên. */
    public static function questionTotal(array $session): int
    {
        return count(Activity::questions((int) $session['activity_id']));
    }

    /** @param array<string,mixed> $input */
    public static function saveConfig(int $sessionId, array $input): array
    {
        $session = self::own($sessionId);
        if ($session === null) {
            return self::fail(404, 'Không tìm thấy phiên hoạt động.');
        }
        if (!in_array($session['status'], ['selected', 'configured'], true)) {
            return self::fail(409, 'Phiên đã bắt đầu nên không đổi được thông số.');
        }

        $text = static fn (string $k): string => isset($input[$k]) && is_scalar($input[$k]) ? trim((string) $input[$k]) : '';
        $errors = [];

        $classId = ctype_digit($text('class_id')) ? (int) $text('class_id') : 0;
        $allowed = array_map('intval', array_column(ClassSession::teacherClasses((int) Auth::id()), 'id'));
        if (!in_array($classId, $allowed, true)) {
            $errors['class_id'] = $allowed === [] ? 'Bạn chưa được phân công lớp dạy nào.' : 'Hãy chọn một lớp bạn đang dạy.';
        }

        $duration = ctype_digit($text('duration_minutes')) ? (int) $text('duration_minutes') : 0;
        if ($duration < 1 || $duration > self::MAX_DURATION) {
            $errors['duration_minutes'] = 'Thời gian phải từ 1 đến ' . self::MAX_DURATION . ' phút.';
        }

        $total = self::questionTotal($session);
        $count = $text('question_count') === '' ? 0 : (ctype_digit($text('question_count')) ? (int) $text('question_count') : -1);
        if ($count < 0 || $count > $total) {
            $errors['question_count'] = $total === 0
                ? 'Hoạt động này chưa có câu hỏi, hãy để 0.'
                : "Số câu hỏi phải từ 0 đến {$total} (0 là dùng tất cả).";
        }

        $playMode = $text('play_mode');
        if (mb_strlen($playMode) > 255) {
            $errors['play_mode'] = 'Cách chơi không được vượt quá 255 ký tự.';
        }

        if ($errors !== []) {
            return self::fail(422, 'Vui lòng kiểm tra lại thông số.', $errors);
        }

        ClassSession::saveConfig($sessionId, $classId, $duration, $count, $playMode === '' ? null : $playMode);
        Logger::activity('config_session', 'session', 'Thiết lập phiên: ' . $session['activity_title'], 'success', null, 'session', $sessionId);
        return self::done(200, 'Đã lưu thông số.', ['id' => $sessionId]);
    }

    // ======================= Điều khiển =======================

    public static function control(int $sessionId, string $command): array
    {
        $session = self::own($sessionId);
        if ($session === null) {
            return self::fail(404, 'Không tìm thấy phiên hoạt động.');
        }

        $changed = null;
        switch ($command) {
            case 'start':
                $changed = $session['class_id'] === null ? -1 : ClassSession::start($sessionId);
                break;
            case 'pause':
                $changed = ClassSession::pause($sessionId);
                break;
            case 'resume':
                $changed = ClassSession::resume($sessionId);
                break;
            case 'end':
                $changed = ClassSession::finish($sessionId);
                break;
            case 'next':
            case 'prev':
            case 'reveal':
                return self::navigate($session, $command);
            default:
                return self::fail(422, 'Lệnh điều khiển không hợp lệ.');
        }

        if ($changed === -1) {
            return self::fail(409, 'Hãy thiết lập thông số (chọn lớp, thời gian) trước khi bắt đầu.');
        }
        if ($changed !== 1) {
            return self::fail(409, 'Lệnh này không áp dụng được ở trạng thái hiện tại của phiên.');
        }

        Logger::activity($command . '_session', 'session', 'Điều khiển phiên (' . $command . '): ' . $session['activity_title'], 'success', null, 'session', $sessionId);
        return self::done(200, 'Đã thực hiện.', self::state($sessionId, 'control'));
    }

    /** Chuyển câu hỏi trước/sau hoặc bật/tắt hiện đáp án (lưu trong config_json). @param array<string,mixed> $session */
    private static function navigate(array $session, string $command): array
    {
        if (!in_array($session['status'], ['configured', 'running', 'paused'], true)) {
            return self::fail(409, 'Chỉ chuyển câu hỏi khi phiên đã thiết lập và chưa kết thúc.');
        }

        $json  = self::json($session);
        $total = self::usableQuestions($session);

        if ($command === 'next') {
            $json['current'] = min(max(0, $total - 1), (int) $json['current'] + 1);
            $json['reveal']  = false;
        } elseif ($command === 'prev') {
            $json['current'] = max(0, (int) $json['current'] - 1);
            $json['reveal']  = false;
        } else {
            $json['reveal'] = !$json['reveal'];
        }
        ClassSession::saveJson((int) $session['id'], json_encode($json));
        return self::done(200, 'Đã thực hiện.', self::state((int) $session['id'], 'control'));
    }

    // ======================= Trạng thái cho điều khiển / trình chiếu =======================

    /**
     * @param string $view 'control' (giáo viên, có cờ đáp án đúng) hoặc 'present' (màn hình chiếu: chỉ lộ đáp án khi bật reveal)
     * @return array<string,mixed>|null
     */
    public static function state(int $sessionId, string $view = 'control'): ?array
    {
        $session = self::own($sessionId);
        if ($session === null) {
            return null;
        }

        $json      = self::json($session);
        $questions = self::limitedQuestions($session);
        $total     = count($questions);
        $current   = $total === 0 ? 0 : min((int) $json['current'], $total - 1);
        $reveal    = (bool) $json['reveal'];

        $duration = (int) $session['duration_minutes'] * 60;
        $elapsed  = (int) $session['elapsed_now'];

        $presentQuestion = static function (array $q, bool $showCorrect): array {
            return [
                'text'        => $q['question_text'],
                'explanation' => $showCorrect ? (string) $q['explanation'] : '',
                'answers'     => array_map(static fn (array $a): array => [
                    'text'       => $a['answer_text'],
                    'is_correct' => $showCorrect ? (bool) $a['is_correct'] : false,
                ], $q['answers']),
            ];
        };

        $activity = Activity::findById((int) $session['activity_id']);   // phiên đã chọn thì vẫn trình chiếu được dù hoạt động bị sửa/xóa sau đó

        $data = [
            'id'               => (int) $session['id'],
            'status'           => $session['status'],
            'status_label'     => self::STATUS_LABELS[$session['status']] ?? $session['status'],
            'title'            => (string) ($activity['title'] ?? $session['activity_title']),
            'type_name'        => $session['type_name'],
            'description'      => (string) ($activity['description'] ?? ''),
            'content'          => (string) ($activity['content'] ?? ''),
            'class_name'       => (string) $session['class_name'],
            'play_mode'        => (string) $session['play_mode'],
            'duration_seconds' => $duration,
            'elapsed_seconds'  => $elapsed,
            'remaining_seconds'=> $duration > 0 ? max(0, $duration - $elapsed) : null,
            'question_total'   => $total,
            'current'          => $current,
            'reveal'           => $reveal,
            'question'         => $total === 0 ? null : $presentQuestion($questions[$current], $view === 'control' || $reveal),
        ];

        if ($view === 'control') {
            $data['questions'] = array_map(static fn (array $q): array => $presentQuestion($q, true), $questions);
        }
        return $data;
    }

    /** @param array<string,mixed> $session @return array{current:int,reveal:bool} */
    private static function json(array $session): array
    {
        $decoded = json_decode((string) $session['config_json'], true);
        $decoded = is_array($decoded) ? $decoded : [];
        return ['current' => (int) ($decoded['current'] ?? 0), 'reveal' => (bool) ($decoded['reveal'] ?? false)];
    }

    /** Các câu hỏi dùng trong phiên: question_count (0 = tất cả) câu đầu tiên. @param array<string,mixed> $session @return array<int,array<string,mixed>> */
    private static function limitedQuestions(array $session): array
    {
        $questions = Activity::questions((int) $session['activity_id']);
        $limit     = (int) $session['question_count'];
        return $limit > 0 ? array_slice($questions, 0, $limit) : $questions;
    }

    private static function usableQuestions(array $session): int
    {
        return count(self::limitedQuestions($session));
    }

    private static function fail(int $status, string $message, array $errors = []): array
    {
        return ['ok' => false, 'status' => $status, 'message' => $message, 'errors' => $errors, 'item' => null];
    }

    private static function done(int $status, string $message, mixed $item): array
    {
        return ['ok' => true, 'status' => $status, 'message' => $message, 'errors' => [], 'item' => $item];
    }
}
