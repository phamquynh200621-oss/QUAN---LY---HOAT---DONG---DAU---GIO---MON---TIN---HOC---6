<?php
/**
 * Bảng nhập kết quả của một phiên.
 * Biến: $session, $students, $counts, $ready, $ratings, $saveBadges, $saveLabels, $apiBase, $controlUrl, $studentsUrl.
 */
$s = $session;
$noResult = 0;
foreach ($students as $st) {
    if ($st['result_id'] === null) {
        $noResult++;
    }
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
  <div>
    <h2 class="h4 mb-1"><?= e($s['activity_title']) ?></h2>
    <span class="text-secondary">Lớp <?= e((string) $s['class_code']) ?> - <?= e($s['type_name']) ?></span>
  </div>
  <a class="btn btn-outline-secondary" href="<?= e($controlUrl) ?>">Về bảng điều khiển</a>
</div>

<div class="alert d-none" id="page-alert" role="alert"></div>

<?php if (!$ready): ?>
  <div class="alert alert-warning" role="alert">Phiên chưa được thiết lập lớp. Hãy thiết lập phiên trước khi ghi kết quả.</div>
<?php elseif ($students === []): ?>
  <div class="alert alert-warning" role="alert">
    Lớp này chưa có học sinh.
    <?php if ($studentsUrl): ?>Hãy thêm học sinh ở <a href="<?= e($studentsUrl) ?>">mục Học sinh</a>.<?php endif; ?>
  </div>
<?php else: ?>
  <p class="text-secondary">
    Đã lưu chính thức: <strong><?= (int) $counts['saved'] ?></strong> ·
    Nháp: <strong><?= (int) $counts['draft'] ?></strong> ·
    Chưa ghi: <strong><?= (int) $noResult ?></strong>.
    Nhập điểm (0 đến 10) và/hoặc chọn mức đánh giá. «Lưu nháp» giữ lại để sửa tiếp, «Lưu chính thức» chốt kết quả.
  </p>

  <form id="sheet-form" data-sheet data-api="<?= e($apiBase) ?>">
    <div class="card">
      <div class="table-scroll">
        <table class="table align-middle mb-0">
          <thead>
            <tr><th style="width:3rem">#</th><th>Mã</th><th>Họ và tên</th><th style="width:8rem">Điểm</th><th style="width:11rem">Mức đánh giá</th><th>Nhận xét</th><th>Trạng thái</th></tr>
          </thead>
          <tbody>
            <?php foreach ($students as $i => $st): ?>
              <tr data-student="<?= (int) $st['student_id'] ?>">
                <td><?= $i + 1 ?></td>
                <td><?= e($st['student_code']) ?></td>
                <td><?= e($st['full_name']) ?></td>
                <td>
                  <input type="number" class="form-control form-control-sm" name="score" min="0" max="10" step="0.25"
                         value="<?= $st['score'] === null ? '' : e(rtrim(rtrim(number_format((float) $st['score'], 2, '.', ''), '0'), '.')) ?>"
                         aria-label="Điểm của <?= e($st['full_name']) ?>">
                </td>
                <td>
                  <select class="form-select form-select-sm" name="rating_level" aria-label="Mức đánh giá của <?= e($st['full_name']) ?>">
                    <option value="">Chưa chọn</option>
                    <?php foreach ($ratings as $r): ?>
                      <option value="<?= e($r) ?>"<?= (string) $st['rating_level'] === $r ? ' selected' : '' ?>><?= e($r) ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td>
                  <input type="text" class="form-control form-control-sm" name="comment" maxlength="1000"
                         value="<?= e((string) $st['comment']) ?>" aria-label="Nhận xét về <?= e($st['full_name']) ?>">
                  <div class="invalid-feedback" data-row-error></div>
                </td>
                <td>
                  <?php if ($st['save_status'] === null): ?>
                    <span class="text-secondary small">Chưa ghi</span>
                  <?php else: ?>
                    <span class="badge <?= e($saveBadges[$st['save_status']] ?? 'text-bg-secondary') ?>"><?= e($saveLabels[$st['save_status']] ?? $st['save_status']) ?></span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="card-footer d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-secondary" data-action="draft">Lưu nháp</button>
        <button type="button" class="btn btn-primary" data-action="final">Lưu chính thức</button>
      </div>
    </div>
  </form>
<?php endif; ?>
