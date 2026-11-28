<?php
/**
 * Bảng điều khiển phiên. Biến: $session, $stateUrl, $controlApi, $presentUrl, $configUrl, $resultsUrl, $listUrl.
 * Nội dung động do assets/js/session.js dựng từ API state.
 */
$s = $session;
?>
<div id="control-panel" data-control data-state-url="<?= e($stateUrl) ?>" data-control-api="<?= e($controlApi) ?>">
  <a href="<?= e($listUrl) ?>">Quay lại phiên của tôi</a>
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mt-2 mb-3">
    <div>
      <h2 class="h4 mb-1"><?= e($s['activity_title']) ?></h2>
      <span class="badge text-bg-secondary" data-status-badge>...</span>
      <span class="text-secondary ms-2">Lớp <?= e((string) $s['class_code']) ?> - <?= e($s['type_name']) ?></span>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-outline-secondary" href="<?= e($presentUrl) ?>" target="_blank" rel="noopener">Mở màn hình trình chiếu</a>
      <a class="btn btn-outline-secondary" href="<?= e($configUrl) ?>" data-config-link>Thiết lập</a>
      <?php if ($resultsUrl): ?><a class="btn btn-outline-secondary" href="<?= e($resultsUrl) ?>">Ghi nhận kết quả</a><?php endif; ?>
    </div>
  </div>

  <div class="alert d-none" id="page-alert" role="alert"></div>

  <div class="row g-4">
    <div class="col-12 col-lg-5">
      <section class="card mb-4">
        <div class="card-body text-center">
          <div class="display-3 fw-bold" data-timer aria-live="off">--:--</div>
          <div class="text-secondary mb-3" data-timer-note></div>
          <div class="d-flex flex-wrap justify-content-center gap-2">
            <button type="button" class="btn btn-success" data-command="start">Bắt đầu</button>
            <button type="button" class="btn btn-warning" data-command="pause">Tạm dừng</button>
            <button type="button" class="btn btn-success" data-command="resume">Tiếp tục</button>
            <button type="button" class="btn btn-danger" data-command="end">Kết thúc</button>
          </div>
        </div>
      </section>

      <section class="card">
        <div class="card-body">
          <h3 class="h6">Chuyển câu hỏi</h3>
          <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-secondary" data-command="prev">Câu trước</button>
            <button type="button" class="btn btn-outline-secondary" data-command="next">Câu sau</button>
            <button type="button" class="btn btn-outline-secondary" data-command="reveal" data-reveal-button>Hiện đáp án</button>
          </div>
          <p class="small text-secondary mt-2 mb-0">Màn hình trình chiếu chỉ lộ đáp án khi bạn bấm «Hiện đáp án».</p>
        </div>
      </section>
    </div>

    <div class="col-12 col-lg-7">
      <section class="card">
        <div class="card-body">
          <h3 class="h5">Câu hỏi <span class="text-secondary" data-question-position></span></h3>
          <div data-question-area><p class="text-secondary mb-0">Hoạt động này không có câu hỏi.</p></div>
        </div>
      </section>
    </div>
  </div>
</div>
