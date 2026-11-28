<?php
/**
 * API JSON của module-result (URL theo mục 6 của kế hoạch).
 *   PUT  /api/sessions/{id}/results          {items:[{student_id, score, rating_level, comment}]}
 *   GET  /api/sessions/{id}/results/status
 *   POST /api/sessions/{id}/results/save     lưu chính thức
 *   GET  /api/results                        ?q&class_id&activity_id&from&to&page
 *   GET  /api/results/{id}/history
 */
final class ResultApiController extends Controller
{
    public function save(array $params): void
    {
        $items = $this->request->input('items', []);
        $this->respond(ResultService::save((int) $params['id'], is_array($items) ? $items : []));
    }

    public function status(array $params): void
    {
        $counts = ResultService::status((int) $params['id']);
        if ($counts === null) {
            $this->error('Không tìm thấy phiên hoạt động.', 404);
        }
        $this->success($counts);
    }

    public function finalize(array $params): void
    {
        $this->respond(ResultService::finalize((int) $params['id']));
    }

    public function index(array $params = []): void
    {
        $result = ResultService::search($this->request->all());
        $this->success(['items' => $result['items'], 'pagination' => $result['pager']]);
    }

    public function history(array $params): void
    {
        $history = ResultService::history((int) $params['id']);
        if ($history === null) {
            $this->error('Không tìm thấy kết quả.', 404);
        }
        $this->success($history);
    }

    /** @param array<string,mixed> $result */
    private function respond(array $result): void
    {
        if (!$result['ok']) {
            $this->error($result['message'], $result['status'], $result['errors']);
        }
        $this->success($result['item'], $result['message'], $result['status']);
    }
}
