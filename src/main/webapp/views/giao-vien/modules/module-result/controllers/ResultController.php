<?php
/** Trang nhập kết quả của một phiên và trang lịch sử kết quả. */
final class ResultController extends Controller
{
    /** GET /sessions/{id}/results */
    public function sheet(array $params): void
    {
        $data = ResultService::sheet((int) $params['id']);
        if ($data === null) {
            Response::abort(404, 'Không tìm thấy phiên hoạt động của bạn.', false);
        }

        $this->view('module-result::sheet', $data + [
            'title'      => 'Ghi nhận kết quả: ' . $data['session']['activity_title'],
            'ratings'    => ResultService::RATINGS,
            'saveBadges' => ResultService::SAVE_BADGES,
            'saveLabels' => ResultService::SAVE_LABELS,
            'apiBase'    => url('/api/sessions/' . $data['session']['id'] . '/results'),
            'controlUrl' => url('/sessions/' . $data['session']['id']),
            'studentsUrl'=> module_exists('module-student') ? url('/students') : null,
            'scripts'    => [module_asset('result', 'js/result.js')],
        ]);
    }

    /** GET /results */
    public function index(array $params = []): void
    {
        $data = ResultService::search($this->request->all());

        $this->view('module-result::index', $data + [
            'title'      => 'Kết quả học sinh',
            'classes'    => ResultService::classOptions(),
            'activities' => ResultService::activityOptions(),
            'baseUrl'    => url('/results'),
            'apiPath'    => url('/api/results'),
            'badges'     => ResultService::SAVE_BADGES,
            'scripts'    => [module_asset('result', 'js/result.js')],
        ]);
    }
}
