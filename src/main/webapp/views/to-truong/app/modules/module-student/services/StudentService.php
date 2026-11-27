<?php
/**
 * Nghiệp vụ danh sách học sinh (dòng 24).
 *
 * Phạm vi: Admin thao tác mọi lớp; Tổ trưởng và Giáo viên (phạm vi 'class') chỉ thấy và sửa học sinh
 * của các lớp được phân công dạy (bảng teacher_classes). Phó Hiệu trưởng chỉ xem.
 * Thêm/sửa: mã học sinh bắt buộc, không trùng; họ tên bắt buộc; ngày sinh (nếu có) hợp lệ và không ở tương lai;
 *           lớp phải tồn tại, đang hoạt động và nằm trong phạm vi được phép.
 * Xóa: không xóa học sinh đã có kết quả (chỉ chuyển "ngừng sử dụng").
 * Nhập CSV: cột student_code, full_name, dob, gender; mỗi dòng lỗi được báo riêng, dòng hợp lệ vẫn được nhập.
 *
 * Kết quả: ['ok'=>bool, 'status'=>int, 'message'=>string, 'errors'=>array, 'item'=>mixed]
 */
final class StudentService
{
    public const PER_PAGE = 20;
    private const IMPORT_MAX_ROWS  = 500;
    private const IMPORT_MAX_BYTES = 1048576;   // 1 MB
    private const MSG_SCOPE = 'Bạn chỉ quản lý được học sinh của lớp mình dạy.';

    /** @return int[]|null null = không giới hạn lớp */
    public static function allowedClassIds(): ?array
    {
        return Permission::scope(24) === 'class' ? Student::teacherClassIds((int) Auth::id()) : null;
    }

    /** @return array<int,array<string,mixed>> các lớp người dùng được thao tác (cho ô chọn) */
    public static function classOptions(): array
    {
        return Student::classes(self::allowedClassIds());
    }

    /**
     * @param array<string,mixed> $input thường là $request->all()
     * @return array{items:array<int,array<string,mixed>>,filters:array<string,mixed>,pager:array<string,int>}
     */
    public static function search(array $input): array
    {
        $q       = isset($input['q']) && is_scalar($input['q']) ? trim((string) $input['q']) : '';
        $classId = isset($input['class_id']) && is_scalar($input['class_id']) && ctype_digit((string) $input['class_id'])
            ? (int) $input['class_id'] : null;

        $filters = ['q' => $q === '' ? null : mb_substr($q, 0, 100), 'class_id' => $classId === 0 ? null : $classId];
        $allowed = self::allowedClassIds();

        $total = Student::count($filters, $allowed);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($pages, max(1, (int) ($input['page'] ?? 1)));
        $pager = ['page' => $page, 'pages' => $pages, 'total' => $total, 'per_page' => self::PER_PAGE];

        return [
            'items'   => Student::search($filters, $allowed, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'filters' => $filters,
            'pager'   => $pager,
        ];
    }

    /** @param array<string,mixed> $input */
    public static function create(array $input): array
    {
        [$data, $errors] = self::sanitize($input, null, self::allowedClassIds(), null);
        if ($errors !== []) {
            return self::fail(422, 'Vui lòng kiểm tra lại thông tin.', $errors);
        }

        $id = Student::add($data);
        Logger::activity('create_student', 'student', 'Thêm học sinh: ' . $data['student_code'] . ' - ' . $data['full_name'], 'success', null, 'student', $id);
        return self::done(201, 'Đã thêm học sinh.', Student::findById($id));
    }

    /** @param array<string,mixed> $input */
    public static function update(int $id, array $input): array
    {
        $student = Student::findById($id);
        if ($student === null) {
            return self::fail(404, 'Không tìm thấy học sinh.');
        }
        $allowed = self::allowedClassIds();
        if (!self::inScope((int) $student['class_id'], $allowed)) {
            return self::fail(403, self::MSG_SCOPE);
        }

        [$data, $errors] = self::sanitize($input, $id, $allowed, (int) $student['class_id']);
        if ($errors !== []) {
            return self::fail(422, 'Vui lòng kiểm tra lại thông tin.', $errors);
        }

        Student::change($id, $data);
        Logger::activity('update_student', 'student', 'Sửa học sinh: ' . $data['student_code'] . ' - ' . $data['full_name'], 'success', null, 'student', $id);
        return self::done(200, 'Đã cập nhật học sinh.', Student::findById($id));
    }

    public static function setStatus(int $id, string $status): array
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            return self::fail(422, 'Trạng thái không hợp lệ.', ['status' => 'Trạng thái không hợp lệ.']);
        }
        $student = Student::findById($id);
        if ($student === null) {
            return self::fail(404, 'Không tìm thấy học sinh.');
        }
        if (!self::inScope((int) $student['class_id'], self::allowedClassIds())) {
            return self::fail(403, self::MSG_SCOPE);
        }

        Student::change($id, ['status' => $status]);
        Logger::activity('status_student', 'student',
            ($status === 'active' ? 'Kích hoạt học sinh: ' : 'Ngừng sử dụng học sinh: ') . $student['student_code'],
            'success', null, 'student', $id);
        return self::done(200, $status === 'active' ? 'Đã kích hoạt lại.' : 'Đã chuyển sang ngừng sử dụng.', Student::findById($id));
    }

    public static function delete(int $id): array
    {
        $student = Student::findById($id);
        if ($student === null) {
            return self::fail(404, 'Không tìm thấy học sinh.');
        }
        if (!self::inScope((int) $student['class_id'], self::allowedClassIds())) {
            return self::fail(403, self::MSG_SCOPE);
        }

        $blocked = 'Không thể xóa học sinh đã có kết quả. Hãy chuyển sang «ngừng sử dụng».';
        if (Student::hasResults($id)) {
            return self::fail(409, $blocked);
        }
        try {
            Student::remove($id);
        } catch (PDOException $e) {
            return self::fail(409, $blocked);
        }

        Logger::activity('delete_student', 'student', 'Xóa học sinh: ' . $student['student_code'] . ' - ' . $student['full_name'], 'success', null, 'student', $id);
        return self::done(200, 'Đã xóa học sinh.', null);
    }

    /**
     * Nhập từ tệp CSV UTF-8 (dấu phân cách , hoặc ;). Dòng đầu là tiêu đề; bắt buộc có cột student_code và full_name.
     * @param array<string,mixed>|null $file phần tử của $_FILES
     */
    public static function import(?array $file, string $classIdRaw): array
    {
        $classId = ctype_digit($classIdRaw) ? (int) $classIdRaw : 0;
        $class   = $classId > 0 ? Student::classInfo($classId) : null;
        if ($class === null || $class['status'] !== 'active') {
            return self::fail(422, 'Hãy chọn lớp hợp lệ.', ['class_id' => 'Hãy chọn lớp hợp lệ.']);
        }
        if (!self::inScope($classId, self::allowedClassIds())) {
            return self::fail(403, self::MSG_SCOPE);
        }

        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return self::fail(422, 'Hãy chọn tệp CSV.', ['file' => 'Hãy chọn tệp CSV.']);
        }
        if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $file['size'] > self::IMPORT_MAX_BYTES) {
            return self::fail(422, 'Tệp không được vượt quá 1 MB.', ['file' => 'Tệp không được vượt quá 1 MB.']);
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            return self::fail(422, 'Tải tệp lên thất bại, hãy thử lại.', ['file' => 'Tải tệp lên thất bại.']);
        }
        if (!in_array(strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)), ['csv', 'txt'], true)) {
            return self::fail(422, 'Chỉ nhận tệp .csv.', ['file' => 'Chỉ nhận tệp .csv.']);
        }
        if (!mb_check_encoding((string) file_get_contents($file['tmp_name']), 'UTF-8')) {
            return self::fail(422, 'Tệp phải lưu dạng UTF-8 (trong Excel: Save As, chọn CSV UTF-8).', ['file' => 'Tệp không phải UTF-8.']);
        }

        $handle = fopen($file['tmp_name'], 'rb');
        if ($handle === false) {
            return self::fail(500, 'Không đọc được tệp.');
        }
        if (fread($handle, 3) !== "\xEF\xBB\xBF") {   // bỏ BOM nếu có
            rewind($handle);
        }
        $firstLine = (string) fgets($handle);
        rewind($handle);
        if (fread($handle, 3) !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $header = fgetcsv($handle, 0, $delimiter, '"', '');
        $index  = [];
        foreach (is_array($header) ? $header : [] as $position => $name) {
            $index[strtolower(trim((string) $name))] = $position;
        }
        if (!isset($index['student_code'], $index['full_name'])) {
            fclose($handle);
            return self::fail(422, 'Dòng đầu tiên phải có các cột student_code và full_name. Hãy tải tệp mẫu để xem định dạng.', ['file' => 'Thiếu cột bắt buộc.']);
        }

        $imported = 0;
        $errors   = [];
        $seen     = [];
        $line     = 1;
        $allowed  = self::allowedClassIds();

        while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            $line++;
            if ($row === [null] || trim(implode('', array_map('strval', $row))) === '') {
                continue;   // dòng trống
            }
            if ($line - 1 > self::IMPORT_MAX_ROWS) {
                $errors[] = ['line' => $line, 'message' => 'Vượt quá ' . self::IMPORT_MAX_ROWS . ' dòng, các dòng sau không được nhập.'];
                break;
            }

            $cell = static fn (string $name): string => isset($index[$name]) ? trim((string) ($row[$index[$name]] ?? '')) : '';
            $input = [
                'student_code' => $cell('student_code'),
                'full_name'    => $cell('full_name'),
                'dob'          => $cell('dob'),
                'gender'       => self::normalizeGender($cell('gender')),
                'class_id'     => $classId,
            ];

            $key = mb_strtolower($input['student_code']);
            if ($key !== '' && isset($seen[$key])) {
                $errors[] = ['line' => $line, 'message' => 'Mã học sinh ' . $input['student_code'] . ' bị trùng trong tệp.'];
                continue;
            }
            $seen[$key] = true;

            [$data, $rowErrors] = self::sanitize($input, null, $allowed, null);
            if ($rowErrors !== []) {
                $errors[] = ['line' => $line, 'message' => implode(' ', $rowErrors)];
                continue;
            }
            try {
                Student::add($data);
                $imported++;
            } catch (PDOException $e) {
                $errors[] = ['line' => $line, 'message' => 'Không lưu được dòng này.'];
            }
        }
        fclose($handle);

        Logger::activity('import_students', 'student',
            "Nhập CSV vào lớp {$class['code']}: {$imported} học sinh thành công, " . count($errors) . ' dòng lỗi',
            $imported > 0 ? 'success' : 'failed', null, 'class', $classId);

        $message = "Đã nhập {$imported} học sinh" . ($errors === [] ? '.' : ', ' . count($errors) . ' dòng lỗi.');
        return self::done(200, $message, ['imported' => $imported, 'failed' => count($errors), 'errors' => $errors]);
    }

    /** Nội dung tệp CSV mẫu (có BOM để Excel đọc đúng tiếng Việt). */
    public static function templateCsv(): string
    {
        return "\xEF\xBB\xBF" . "student_code,full_name,dob,gender\r\n"
            . "HS001,Nguyễn Văn An,2014-05-20,Nam\r\n"
            . "HS002,Trần Thị Bình,2014-09-02,Nữ\r\n";
    }

    private static function inScope(int $classId, ?array $allowed): bool
    {
        return $allowed === null || in_array($classId, $allowed, true);
    }

    private static function normalizeGender(string $value): string
    {
        $v = mb_strtolower(trim($value));
        return match ($v) {
            'nam', 'm', 'male'                     => 'M',
            'nữ', 'nu', 'f', 'female'              => 'F',
            default                                => '',
        };
    }

    /** Nhận Y-m-d hoặc d/m/Y; trả về Y-m-d hoặc null nếu sai. */
    private static function parseDate(string $text): ?string
    {
        foreach (['Y-m-d', 'd/m/Y'] as $format) {
            $date = DateTime::createFromFormat($format, $text);
            if ($date !== false && $date->format($format) === $text) {
                return $date->format('Y-m-d');
            }
        }
        return null;
    }

    /**
     * @param array<string,mixed> $input
     * @param int[]|null $allowed lớp được phép
     * @param int|null $currentClassId lớp hiện tại của học sinh khi sửa (được giữ dù lớp đã ngừng sử dụng)
     * @return array{0:array<string,mixed>,1:array<string,string>}
     */
    private static function sanitize(array $input, ?int $exceptId, ?array $allowed, ?int $currentClassId): array
    {
        $text = static fn (string $key): string => isset($input[$key]) && is_scalar($input[$key]) ? trim((string) $input[$key]) : '';

        $strings = ['student_code' => $text('student_code'), 'full_name' => $text('full_name')];
        $errors  = Validator::validate(
            $strings,
            ['student_code' => 'required|max:30', 'full_name' => 'required|max:150'],
            ['student_code' => 'Mã học sinh', 'full_name' => 'Họ và tên']
        );

        if (!isset($errors['student_code'])) {
            if (preg_match('/^[A-Za-z0-9._-]+$/', $strings['student_code']) !== 1) {
                $errors['student_code'] = 'Mã học sinh chỉ gồm chữ, số và các dấu . _ -';
            } elseif (Student::codeExists($strings['student_code'], $exceptId)) {
                $errors['student_code'] = 'Mã học sinh này đã tồn tại.';
            }
        }

        $dob = null;
        $dobText = $text('dob');
        if ($dobText !== '') {
            $dob = self::parseDate($dobText);
            if ($dob === null || $dob > date('Y-m-d')) {
                $errors['dob'] = 'Ngày sinh không hợp lệ.';
            }
        }

        $gender = $text('gender');
        if (!in_array($gender, ['', 'M', 'F'], true)) {
            $errors['gender'] = 'Giới tính không hợp lệ.';
        }

        $classRaw = $text('class_id');
        $classId  = ctype_digit($classRaw) ? (int) $classRaw : 0;
        $class    = $classId > 0 ? Student::classInfo($classId) : null;
        if ($class === null) {
            $errors['class_id'] = 'Hãy chọn lớp hợp lệ.';
        } elseif ($class['status'] !== 'active' && $classId !== $currentClassId) {
            $errors['class_id'] = 'Lớp này đã ngừng sử dụng.';
        } elseif (!self::inScope($classId, $allowed)) {
            $errors['class_id'] = 'Bạn chỉ được chọn lớp mình dạy.';
        }

        $data = [
            'student_code' => $strings['student_code'],
            'full_name'    => $strings['full_name'],
            'dob'          => $dob,
            'gender'       => $gender === '' ? null : $gender,
            'class_id'     => $classId,
        ];
        return [$data, $errors];
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
