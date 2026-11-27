<?php
/**
 * API JSON của module-log (chỉ đọc).
 *   GET /api/logs/system   ?from&to&user_id&action&page
 *   GET /api/logs/backups  ?from&to&operation&status&page
 */
final class LogApiController extends Controller
{
    public function system(array $params = []): void
    {
        $result = LogService::systemLogs($this->request->all());

        $items = array_map(static fn (array $r): array => [
            'id'         => (int) $r['id'],
            'created_at' => $r['created_at'],
            'user'       => $r['full_name'],
            'action'     => $r['action'],
            'action_label' => LogService::actionLabel((string) $r['action']),
            'module'     => $r['module'],
            'detail'     => $r['detail'],
            'ip_address' => $r['ip_address'],
            'status'     => $r['status'],
        ], $result['items']);

        $this->success(['items' => $items, 'pagination' => $this->pagination($result['pager'])]);
    }

    public function backups(array $params = []): void
    {
        $result = LogService::backupLogs($this->request->all());

        $items = array_map(static fn (array $r): array => [
            'id'          => (int) $r['id'],
            'operation'   => $r['operation'],
            'status'      => $r['status'],
            'started_at'  => $r['started_at'],
            'finished_at' => $r['finished_at'],
            'file_name'   => $r['file_name'],
            'file_size'   => $r['file_size'] === null ? null : (int) $r['file_size'],
            'performed_by' => $r['full_name'],
            'message'     => $r['message'],
        ], $result['items']);

        $this->success(['items' => $items, 'pagination' => $this->pagination($result['pager'])]);
    }

    /** @param array<string,int> $pager */
    private function pagination(array $pager): array
    {
        return [
            'page'     => $pager['page'],
            'pages'    => $pager['pages'],
            'total'    => $pager['total'],
            'per_page' => $pager['per_page'],
        ];
    }
}
