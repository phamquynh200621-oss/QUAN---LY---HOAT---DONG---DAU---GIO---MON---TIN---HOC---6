<?php
/**
 * API JSON của module-session (URL theo mục 6 của kế hoạch).
 *   GET  /api/activities/{id}/preview
 *   POST /api/sessions               {activity_id}
 *   GET  /api/sessions               ?page=
 *   PUT  /api/sessions/{id}/config   {class_id, duration_minutes, question_count, play_mode}
 *   POST /api/sessions/{id}/control  {command: start|pause|resume|end|next|prev|reveal}
 *   GET  /api/sessions/{id}/state    ?view=present (màn hình chiếu: không lộ đáp án khi chưa bật reveal)
 */
final class SessionApiController extends Controller
{
    public function preview(array $params): void
    {
        $activity = SessionService::preview((int) $params['id']);
        if ($activity === null) {
            $this->error('Không tìm thấy hoạt động.', 404);
        }
        $this->success($activity);
    }

    public function create(array $params = []): void
    {
        $activityId = $this->request->string('activity_id');
        $this->respond(SessionService::create(ctype_digit($activityId) ? (int) $activityId : 0));
    }

    public function index(array $params = []): void
    {
        $result = SessionService::mine($this->request->all());
        $this->success(['items' => $result['items'], 'pagination' => $result['pager']]);
    }

    public function config(array $params): void
    {
        $this->respond(SessionService::saveConfig((int) $params['id'], $this->request->all()));
    }

    public function control(array $params): void
    {
        $this->respond(SessionService::control((int) $params['id'], $this->request->string('command')));
    }

    public function state(array $params): void
    {
        $view  = $this->request->string('view') === 'present' ? 'present' : 'control';
        $state = SessionService::state((int) $params['id'], $view);
        if ($state === null) {
            $this->error('Không tìm thấy phiên hoạt động.', 404);
        }
        $this->success($state);
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
