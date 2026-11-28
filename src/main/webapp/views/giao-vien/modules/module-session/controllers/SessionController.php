<?php
/** Các trang của module-session. */
final class SessionController extends Controller
{
    /** GET /sessions/select - chọn hoạt động đã duyệt để tổ chức. */
    public function select(array $params = []): void
    {
        $data = SessionService::selectable($this->request->all());

        $this->view('module-session::select', $data + SearchService::filterOptions() + [
            'title'   => 'Chọn hoạt động để tổ chức',
            'baseUrl' => url('/sessions/select'),
            'canRun'  => Permission::can(15, 'T'),
            'mineUrl' => url('/sessions'),
        ]);
    }

    /** GET /activities/{id}/preview - xem trước hoạt động. */
    public function preview(array $params): void
    {
        $activity = SessionService::preview((int) $params['id']);
        if ($activity === null) {
            Response::abort(404, 'Không tìm thấy hoạt động hoặc bạn không có quyền xem.', false);
        }

        $this->view('module-session::preview', [
            'title'      => 'Xem trước: ' . $activity['title'],
            'activity'   => $activity,
            'selectUrl'  => url('/sessions/select'),
            'sessionApi' => url('/api/sessions'),
            'sessionsBase' => url('/sessions'),
            'scripts'    => [module_asset('session', 'js/session.js')],
        ]);
    }

    /** GET /sessions - các phiên của tôi. */
    public function mine(array $params = []): void
    {
        $data = SessionService::mine($this->request->all());

        $this->view('module-session::list', $data + [
            'title'        => 'Phiên hoạt động của tôi',
            'baseUrl'      => url('/sessions'),
            'filters'      => [],
            'badges'       => SessionService::STATUS_BADGES,
            'selectUrl'    => url('/sessions/select'),
            'canRecord'    => module_exists('module-result') && Permission::can(16, 'T'),
        ]);
    }

    /** GET /sessions/{id}/config */
    public function config(array $params): void
    {
        $session = $this->ownSession((int) $params['id']);

        $this->view('module-session::config', [
            'title'         => 'Thiết lập: ' . $session['activity_title'],
            'session'       => $session,
            'classes'       => ClassSession::teacherClasses((int) Auth::id()),
            'questionTotal' => SessionService::questionTotal($session),
            'editable'      => in_array($session['status'], ['selected', 'configured'], true),
            'controlUrl'    => url('/sessions/' . $session['id']),
            'apiUrl'        => url('/api/sessions/' . $session['id'] . '/config'),
            'badges'        => SessionService::STATUS_BADGES,
            'statusLabel'   => SessionService::STATUS_LABELS[$session['status']] ?? $session['status'],
            'scripts'       => [module_asset('session', 'js/session.js')],
        ]);
    }

    /** GET /sessions/{id} - bảng điều khiển. */
    public function control(array $params): void
    {
        $session = $this->ownSession((int) $params['id']);

        $this->view('module-session::control', [
            'title'      => 'Điều khiển: ' . $session['activity_title'],
            'session'    => $session,
            'stateUrl'   => url('/api/sessions/' . $session['id'] . '/state'),
            'controlApi' => url('/api/sessions/' . $session['id'] . '/control'),
            'presentUrl' => url('/sessions/' . $session['id'] . '/present'),
            'configUrl'  => url('/sessions/' . $session['id'] . '/config'),
            'resultsUrl' => module_exists('module-result') && Permission::can(16, 'T') ? url('/sessions/' . $session['id'] . '/results') : null,
            'listUrl'    => url('/sessions'),
            'scripts'    => [module_asset('session', 'js/session.js')],
        ]);
    }

    /** GET /sessions/{id}/present - màn hình chiếu cho cả lớp. */
    public function present(array $params): void
    {
        $session = $this->ownSession((int) $params['id']);

        $this->view('module-session::present', [
            'title'    => $session['activity_title'],
            'stateUrl' => url('/api/sessions/' . $session['id'] . '/state?view=present'),
            'styles'   => [module_asset('session', 'css/present.css')],
            'scripts'  => [module_asset('session', 'js/session.js')],
        ], 'layouts/presentation');
    }

    /** @return array<string,mixed> */
    private function ownSession(int $id): array
    {
        $session = SessionService::own($id);
        if ($session === null) {
            Response::abort(404, 'Không tìm thấy phiên hoạt động của bạn.', false);
        }
        return $session;
    }
}
