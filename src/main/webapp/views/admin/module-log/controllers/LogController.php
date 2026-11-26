<?php
/** Các trang nhật ký (chỉ đọc). */
final class LogController extends Controller
{
    /** GET /logs/system - dòng 25, chỉ Admin. */
    public function system(array $params = []): void
    {
        $data = LogService::systemLogs($this->request->all());

        $this->view('module-log::system', $data + [
            'title'   => 'Nhật ký hệ thống',
            'users'   => SystemLog::users(),
            'actions' => SystemLog::actions(),
            'baseUrl' => url('/logs/system'),
        ]);
    }

    /** GET /logs/backups - dòng 23, Admin và Phó Hiệu trưởng. */
    public function backups(array $params = []): void
    {
        $data = LogService::backupLogs($this->request->all());

        $this->view('module-log::backups', $data + [
            'title'   => 'Nhật ký sao lưu và phục hồi',
            'baseUrl' => url('/logs/backups'),
        ]);
    }
}
