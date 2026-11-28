<?php
/**
 * Nghiệp vụ kết quả học sinh (dòng 16, 17).
 *
 * MDVI1.1 Ghi nhận / cập nhật kết quả
 *   Input : mã học sinh, điểm (0-10, tối đa 2 chữ số thập phân) và/hoặc mức đánh giá, nhận xét
 *   Process: chỉ phiên của chính mình, đã thiết lập lớp; học sinh phải thuộc lớp của phiên; nếu một dòng sai thì không lưu dòng nào.
 *            Mỗi lần ghi hoặc sửa tạo một dòng lịch sử (result_histories).
 * MDVI1.2 Quản lý trạng thái lưu: 'draft' (nháp) -> 'saved' (lưu chính thức). Sửa lại kết quả đã lưu đưa nó về nháp.
 * MDVI2.2 Xem lại lịch sử: lọc theo lớp, hoạt động, học sinh, khoảng ngày; xem chi tiết từng lần đánh giá.
 *
 * Kết quả: ['ok'=>bool, 'status'=>int, 'message'=>string, 'errors'=>array, 'item'=>mixed]
 */
final class ResultService
{
    public const PER_PAGE = 20;
    /** Mức đánh giá (giả định của nhóm phát triển, sửa tại đây nếu trường dùng thang khác). */
    public const RATINGS = ['Tốt', 'Khá', 'Đạt', 'Chưa đạt'];

    public const SAVE_LABELS = ['draft' => 'Nháp', 'saved' => 'Đã lưu', 'failed' => 'Lưu lỗi'];
    public const SAVE_BADGES = ['draft' => 'text-bg-warning', 'saved' => 'text-bg-success', 'failed' => 'text-bg-danger'];

    // ======================= Ghi nhận =======================

    /**
     * Dữ liệu bảng nhập kết quả của một phiên; null nếu không phải phiên của mình.
     * @return array{session:array,students:array,counts:array,ready:bool}|null
     */
    public static function sheet(int $sessionId): ?array
    {
        $session = SessionService::own($sessionId);
        if ($session === null) {
            return null;
        }
        $ready    = $session['class_id'] !== null;
        $students = $ready ? Result::sheet($sessionId, (int) $session['class_id']) : [];

        return ['session' => $session, 'students' => $students, 'counts' => Result::counts($sessionId), 'ready' => $ready];
    }

    /** @param array<int,mixed> $items mỗi phần tử: student_id, score, rating_level, comment */
    public static function save(int $sessionId, array $items): array
    {
        $session = SessionService::own($sessionId);
        if ($session === null) {
            return self::fail(404, 'Không tìm thấy phiên hoạt động của bạn.');
        }
        if ($session['class_id'] === null) {
            return self::fail(409, 'Hãy thiết lập phiên (chọn lớp) trước khi ghi kết quả.');
        }
        if ($items === [] || count($items) > 300) {
            return self::fail(422, 'Không có dữ liệu để lưu.');
        }

        $classId  = (int) $session['class_id'];
        $statuses = Result::studentStatuses($classId);
        $clean    = [];
        $errors   = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $studentId = isset($item['student_id']) && is_scalar($item['student_id']) && ctype_digit((string) $item['student_id'])
                ? (int) $item['student_id'] : 0;
            $key = 'student_' . $studentId;

            if (!isset($statuses[$studentId])) {
                $errors[$key] = 'Học sinh không thuộc lớp của phiên này.';
                continue;
            }

            $scoreText = isset($item['score']) && is_scalar($item['score']) ? str_replace(',', '.', trim((string) $item['score'])) : '';
            $score     = null;
            if ($scoreText !== '') {
                if (preg_match('/^\d+(\.\d{1,2})?$/', $scoreText) !== 1 || (float) $scoreText > 10) {
                    $errors[$key] = 'Điểm phải từ 0 đến 10 (tối đa 2 chữ số thập phân).';
                    continue;
                }
                $score = (float) $scoreText;
            }

            $rating = isset($item['rating_level']) && is_scalar($item['rating_level']) ? trim((string) $item['rating_level']) : '';
            if ($rating !== '' && !in_array($rating, self::RATINGS, true)) {
                $errors[$key] = 'Mức đánh giá không hợp lệ.';
                continue;
            }

            $comment = isset($item['comment']) && is_scalar($item['comment']) ? trim((string) $item['comment']) : '';
            if (mb_strlen($comment) > 1000) {
                $errors[$key] = 'Nhận xét không được vượt quá 1000 ký tự.';
                continue;
            }

            $clean[$studentId] = ['score' => $score, 'rating' => $rating === '' ? null : $rating, 'comment' => $comment === '' ? null : $comment];
        }

        if ($errors !== []) {
            return self::fail(422, 'Có ' . count($errors) . ' dòng chưa hợp lệ, chưa lưu dòng nào.', $errors);
        }

        $userId     = (int) Auth::id();
        $activityId = (int) $session['activity_id'];
        $stats      = ['created' => 0, 'updated' => 0];

        Database::transaction(static function () use ($sessionId, $classId, $activityId, $clean, $userId, &$stats): void {
            foreach ($clean as $studentId => $data) {
                $existing = Result::findForSession($sessionId, $studentId);

                if ($existing === null) {
                    if ($data['score'] === null && $data['rating'] === null && $data['comment'] === null) {
                        continue;   // dòng trống, chưa có gì để ghi
                    }
                    $resultId = Result::add($sessionId, $studentId, $activityId, $classId, $data['score'], $data['rating'], $data['comment'], $userId);
                    Result::addHistory($resultId, $data['score'], $data['rating'], $data['comment'], $userId);
                    $stats['created']++;
                    continue;
                }

                $same = self::sameScore($existing['score'], $data['score'])
                    && ($existing['rating_level'] ?? null) === $data['rating']
                    && ($existing['comment'] ?? null) === $data['comment'];
                if ($same) {
                    continue;
                }
                Result::change((int) $existing['id'], $data['score'], $data['rating'], $data['comment'], $userId);
                Result::addHistory((int) $existing['id'], $data['score'], $data['rating'], $data['comment'], $userId);
                $stats['updated']++;
            }
        });

        Logger::activity(
            'record_results', 'result',
            "Ghi nhận kết quả phiên #{$sessionId}: thêm {$stats['created']}, sửa {$stats['updated']}",
            'success', $userId, 'session', $sessionId
        );
        return self::done(200, "Đã lưu nháp: thêm {$stats['created']}, sửa {$stats['updated']} kết quả.", ['counts' => Result::counts($sessionId)] + $stats);
    }

    /** Lưu chính thức các kết quả còn ở trạng thái nháp. */
    public static function finalize(int $sessionId): array
    {
        $session = SessionService::own($sessionId);
        if ($session === null) {
            return self::fail(404, 'Không tìm thấy phiên hoạt động của bạn.');
        }

        $counts = Result::counts($sessionId);
        if ($counts['draft'] + $counts['saved'] + $counts['failed'] === 0) {
            return self::fail(409, 'Chưa có kết quả nào để lưu.');
        }

        $changed = Result::markSaved($sessionId);
        Logger::activity('save_results', 'result', "Lưu chính thức {$changed} kết quả phiên #{$sessionId}", 'success', null, 'session', $sessionId);
        return self::done(200, $changed > 0 ? "Đã lưu chính thức {$changed} kết quả." : 'Mọi kết quả đã được lưu chính thức.', ['counts' => Result::counts($sessionId)]);
    }

    /** @return array{draft:int,saved:int,failed:int}|null */
    public static function status(int $sessionId): ?array
    {
        return SessionService::own($sessionId) === null ? null : Result::counts($sessionId);
    }

    // ======================= Lịch sử =======================

    /** Các lớp người dùng được xem lịch sử: null = mọi lớp, mảng = các lớp mình dạy (phạm vi 'class'). @return int[]|null */
    public static function allowedClassIds(): ?array
    {
        return Permission::scope(17) === 'class' ? Student::teacherClassIds((int) Auth::id()) : null;
    }

    /**
     * @param array<string,mixed> $input
     * @return array{items:array,filters:array,pager:array}
     */
    public static function search(array $input): array
    {
        $int = static fn (string $k): ?int => (isset($input[$k]) && is_scalar($input[$k]) && ctype_digit((string) $input[$k]) && (int) $input[$k] > 0)
            ? (int) $input[$k] : null;
        $date = static function (string $k) use ($input): ?string {
            $text = isset($input[$k]) && is_scalar($input[$k]) ? trim((string) $input[$k]) : '';
            $d = DateTime::createFromFormat('Y-m-d', $text);
            return ($d !== false && $d->format('Y-m-d') === $text) ? $text : null;
        };
        $q = isset($input['q']) && is_scalar($input['q']) ? trim((string) $input['q']) : '';

        $filters = [
            'q'           => $q === '' ? null : mb_substr($q, 0, 100),
            'class_id'    => $int('class_id'),
            'activity_id' => $int('activity_id'),
            'from'        => $date('from'),
            'to'          => $date('to'),
        ];
        $allowed = self::allowedClassIds();

        $total = Result::count($filters, $allowed);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($pages, max(1, (int) ($input['page'] ?? 1)));

        $me    = (int) Auth::id();
        $canRecord = Permission::can(16, 'T');
        $items = array_map(static function (array $r) use ($me, $canRecord): array {
            return [
                'id'             => (int) $r['id'],
                'session_id'     => (int) $r['session_id'],
                'student_code'   => $r['student_code'],
                'student_name'   => $r['student_name'],
                'class_code'     => $r['class_code'],
                'activity_title' => $r['activity_title'],
                'score'          => $r['score'] === null ? null : (float) $r['score'],
                'rating_level'   => (string) $r['rating_level'],
                'comment'        => (string) $r['comment'],
                'save_status'    => $r['save_status'],
                'save_label'     => self::SAVE_LABELS[$r['save_status']] ?? $r['save_status'],
                'save_badge'     => self::SAVE_BADGES[$r['save_status']] ?? 'text-bg-secondary',
                'recorder_name'  => $r['recorder_name'],
                'updated_at'     => $r['updated_at'],
                'can_edit'       => $canRecord && (int) $r['teacher_id'] === $me,
            ];
        }, Result::search($filters, $allowed, self::PER_PAGE, ($page - 1) * self::PER_PAGE));

        return [
            'items'   => $items,
            'filters' => $filters,
            'pager'   => ['page' => $page, 'pages' => $pages, 'total' => $total, 'per_page' => self::PER_PAGE],
        ];
    }

    /** Chi tiết từng lần đánh giá của một kết quả; null nếu không có hoặc ngoài phạm vi được xem. */
    public static function history(int $resultId): ?array
    {
        $result = Result::findById($resultId);
        if ($result === null) {
            return null;
        }
        $allowed = self::allowedClassIds();
        if ($allowed !== null && !in_array((int) $result['class_id'], $allowed, true)) {
            return null;
        }

        return [
            'result'  => ['id' => (int) $result['id'], 'student' => $result['student_code'] . ' - ' . $result['student_name'], 'activity' => $result['activity_title']],
            'entries' => array_map(static fn (array $h): array => [
                'id'           => (int) $h['id'],
                'score'        => $h['score'] === null ? null : (float) $h['score'],
                'rating_level' => (string) $h['rating_level'],
                'comment'      => (string) $h['comment'],
                'changed_by'   => $h['changed_by_name'],
                'changed_at'   => $h['changed_at'],
            ], Result::history($resultId)),
        ];
    }

    /** Lớp cho ô lọc ở trang lịch sử. @return array<int,array<string,mixed>> */
    public static function classOptions(): array
    {
        return Student::classes(self::allowedClassIds());
    }

    public static function activityOptions(): array
    {
        return Result::activityOptions(self::allowedClassIds());
    }

    private static function sameScore(mixed $old, ?float $new): bool
    {
        if ($old === null || $new === null) {
            return $old === null && $new === null;
        }
        return abs((float) $old - $new) < 0.001;
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
