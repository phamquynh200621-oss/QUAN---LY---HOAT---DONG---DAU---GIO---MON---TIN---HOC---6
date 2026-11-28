<?php
/**
 * Lịch sử kết quả học sinh.
 * Biến: $items, $filters, $pager, $classes, $activities, $baseUrl, $apiPath, $badges.
 */
?>
<div id="history-page" data-history data-api="<?= e($apiPath) ?>">
  <form method="get" action="<?= e($baseUrl) ?>" class="row g-2 align-items-end mb-3">
    <div class="col-12 col-md-4 col-xl-2">
      <label class="form-label mb-1" for="q">Học sinh (mã hoặc tên)</label>
      <input type="search" class="form-control" id="q" name="q" value="<?= e((string) $filters['q']) ?>" maxlength="100">
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <label class="form-label mb-1" for="class_id">Lớp</label>
      <select class="form-select" id="class_id" name="class_id">
        <option value="">Tất cả</option>
        <?php foreach ($classes as $c): ?>
          <option value="<?= (int) $c['id'] ?>"<?= $filters['class_id'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['code']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
      <label class="form-label mb-1" for="activity_id">Hoạt động</label>
      <select class="form-select" id="activity_id" name="activity_id">
        <option value="">Tất cả</option>
        <?php foreach ($activities as $a): ?>
          <option value="<?= (int) $a['id'] ?>"<?= $filters['activity_id'] === (int) $a['id'] ? ' selected' : '' ?>><?= e($a['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
      <label class="form-label mb-1" for="from">Từ ngày</label>
      <input type="date" class="form-control" id="from" name="from" value="<?= e((string) $filters['from']) ?>">
    </div>
    <div class="col-6 col-md-3 col-xl-2">
      <label class="form-label mb-1" for="to">Đến ngày</label>
      <input type="date" class="form-control" id="to" name="to" value="<?= e((string) $filters['to']) ?>">
    </div>
    <div class="col-auto d-flex gap-2">
      <button type="submit" class="btn btn-primary">Lọc</button>
      <a class="btn btn-outline-secondary" href="<?= e($baseUrl) ?>">Đặt lại</a>
    </div>
  </form>

  <p class="text-secondary mb-2"><?= number_format((int) $pager['total'], 0, ',', '.') ?> kết quả</p>

  <div class="card">
    <div class="table-scroll">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr><th>Cập nhật</th><th>Học sinh</th><th>Lớp</th><th>Hoạt động</th><th>Điểm</th><th>Mức</th><th>Nhận xét</th><th>Trạng thái</th><th>Người ghi</th><th></th></tr>
        </thead>
        <tbody>
          <?php if ($items === []): ?>
            <tr><td colspan="10" class="text-center text-secondary py-4">Không có kết quả nào khớp bộ lọc.</td></tr>
          <?php endif; ?>
          <?php foreach ($items as $r): ?>
            <tr>
              <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime((string) $r['updated_at']))) ?></td>
              <td><?= e($r['student_code']) ?> - <?= e($r['student_name']) ?></td>
              <td><?= e($r['class_code']) ?></td>
              <td><?= e($r['activity_title']) ?></td>
              <td><?= $r['score'] === null ? '' : e(rtrim(rtrim(number_format($r['score'], 2, '.', ''), '0'), '.')) ?></td>
              <td><?= e($r['rating_level']) ?></td>
              <td><?= e($r['comment']) ?></td>
              <td><span class="badge <?= e($r['save_badge']) ?>"><?= e($r['save_label']) ?></span></td>
              <td><?= e($r['recorder_name']) ?></td>
              <td class="text-end text-nowrap">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-history-btn data-id="<?= (int) $r['id'] ?>">Lịch sử</button>
                <?php if ($r['can_edit']): ?>
                  <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/sessions/' . $r['session_id'] . '/results')) ?>">Sửa</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php require __DIR__ . '/_pagination.php'; ?>
</div>

<div class="modal fade" id="history-modal" tabindex="-1" aria-labelledby="history-modal-title" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="history-modal-title">Lịch sử đánh giá</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body" data-history-body></div>
    </div>
  </div>
</div>
