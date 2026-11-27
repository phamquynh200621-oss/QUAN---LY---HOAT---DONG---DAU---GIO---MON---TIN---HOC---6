<?php
/**
 * API JSON của module-student.
 *   GET    /api/students                 ?q&class_id&page
 *   POST   /api/students                 {student_code, full_name, dob, gender, class_id}
 *   POST   /api/students/import          multipart: file (CSV), class_id
 *   PUT    /api/students/{id}
 *   PATCH  /api/students/{id}/status     {status: active|inactive}
 *   DELETE /api/students/{id}            409 nếu học sinh đã có kết quả
 * Quyền kiểm tra bằng middleware ở routes.php; phạm vi lớp dạy kiểm tra trong StudentService.
 */
final class StudentApiController extends Controller
{
    public function index(array $params = []): void
    {
        $result = StudentService::search($this->request->all());
        $this->success(['items' => $result['items'], 'pagination' => $result['pager']]);
    }

    public function store(array $params = []): void
    {
        $this->respond(StudentService::create($this->request->all()));
    }

    public function update(array $params): void
    {
        $this->respond(StudentService::update($this->id($params), $this->request->all()));
    }

    public function status(array $params): void
    {
        $this->respond(StudentService::setStatus($this->id($params), $this->request->string('status')));
    }

    public function destroy(array $params): void
    {
        $this->respond(StudentService::delete($this->id($params)));
    }

    public function import(array $params = []): void
    {
        $this->respond(StudentService::import($this->request->file('file'), $this->request->string('class_id')));
    }

    private function id(array $params): int
    {
        $id = (string) ($params['id'] ?? '');
        if (!ctype_digit($id) || (int) $id < 1) {
            $this->error('Mã không hợp lệ.', 404);
        }
        return (int) $id;
    }

    /** @param array<string,mixed> $result kết quả của StudentService */
    private function respond(array $result): void
    {
        if (!$result['ok']) {
            $this->error($result['message'], $result['status'], $result['errors']);
        }
        $this->success($result['item'], $result['message'], $result['status']);
    }
}
