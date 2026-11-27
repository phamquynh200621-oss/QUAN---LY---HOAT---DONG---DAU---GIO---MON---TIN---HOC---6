<?php
/** Trang danh sách học sinh và tệp CSV mẫu. */
final class StudentController extends Controller
{
    public function index(array $params = []): void
    {
        $data    = StudentService::search($this->request->all());
        $classes = StudentService::classOptions();
        $canEdit = Permission::can(24, 'T');

        $this->view('module-student::index', $data + [
            'title'     => 'Danh sách học sinh',
            'classes'   => $classes,
            'canEdit'   => $canEdit,
            'noClasses' => $canEdit && $classes === [],   // chưa được phân công lớp dạy (hoặc chưa có lớp)
            'apiPath'   => url('/api/students'),
            'pagePath'  => url('/students'),
            'baseUrl'   => url('/students'),
            'scripts'   => [module_asset('student', 'js/student.js')],
        ]);
    }

    /** GET /students/template - tải tệp CSV mẫu. */
    public function template(array $params = []): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="mau-danh-sach-hoc-sinh.csv"');
        echo StudentService::templateCsv();
        exit;
    }
}
