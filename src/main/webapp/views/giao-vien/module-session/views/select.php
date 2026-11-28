<?php
/** Chọn hoạt động đã duyệt để tổ chức. Biến: $items, $filters, $pager, $topics, $types, $lessonsByTopic, $baseUrl, $canRun, $mineUrl. */
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <p class="text-secondary mb-0">Chỉ hiện các hoạt động đã được duyệt. Xem trước rồi xác nhận chọn để bắt đầu thiết lập.</p>
  <?php if ($canRun): ?><a class="btn btn-outline-secondary" href="<?= e($mineUrl) ?>">Phiên của tôi</a><?php endif; ?>
</div>

<form method="get" action="<?= e($baseUrl) ?>" class="row g-2 align-items-end mb-3">
  <div class="col-12 col-md-4 col-xl-3">
    <label class="form-label mb-1" for="q">Tên hoạt động</label>
    <input type="search" class="form-control" id="q" name="q" value="<?= e((string) $filters['q']) ?>" maxlength="100">
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <label class="form-label mb-1" for="topic_id">Chủ đề</label>
    <select class="form-select" id="topic_id" name="topic_id">
      <option value="">Tất cả</option>
      <?php foreach ($topics as $t): ?>
        <option value="<?= (int) $t['id'] ?>"<?= $filters['topic_id'] === (int) $t['id'] ? ' selected' : '' ?>><?= e($t['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-4 col-xl-3">
    <label class="form-label mb-1" for="lesson_id">Bài học</label>
    <select class="form-select" id="lesson_id" name="lesson_id">
      <option value="">Tất cả</option>
      <?php foreach ($topics as $t): ?>
        <?php if (!empty($lessonsByTopic[(int) $t['id']])): ?>
          <optgroup label="<?= e($t['name']) ?>">
            <?php foreach ($lessonsByTopic[(int) $t['id']] as $l): ?>
              <option value="<?= (int) $l['id'] ?>"<?= $filters['lesson_id'] === (int) $l['id'] ? ' selected' : '' ?>><?= e($l['name']) ?></option>
            <?php endforeach; ?>
          </optgroup>
        <?php endif; ?>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <label class="form-label mb-1" for="type_id">Hình thức</label>
    <select class="form-select" id="type_id" name="type_id">
      <option value="">Tất cả</option>
      <?php foreach ($types as $y): ?>
        <option value="<?= (int) $y['id'] ?>"<?= $filters['type_id'] === (int) $y['id'] ? ' selected' : '' ?>><?= e($y['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto d-flex gap-2">
    <button type="submit" class="btn btn-primary">Tìm</button>
    <a class="btn btn-outline-secondary" href="<?= e($baseUrl) ?>">Đặt lại</a>
  </div>
</form>

<p class="text-secondary mb-2"><?= number_format((int) $pager['total'], 0, ',', '.') ?> hoạt động đã duyệt</p>

<div class="card">
  <div class="table-scroll">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Tên hoạt động</th><th>Chủ đề</th><th>Bài học</th><th>Hình thức</th><th>Người tạo</th><th></th></tr></thead>
      <tbody>
        <?php if ($items === []): ?>
          <tr><td colspan="6" class="text-center text-secondary py-4">Chưa có hoạt động đã duyệt phù hợp.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $a): ?>
          <tr>
            <td><?= e($a['title']) ?></td>
            <td><?= e($a['topic_name']) ?></td>
            <td><?= e($a['lesson_name']) ?></td>
            <td><?= e($a['type_name']) ?></td>
            <td><?= e($a['creator_name']) ?></td>
            <td class="text-end"><a class="btn btn-primary btn-sm" href="<?= e(url('/activities/' . $a['id'] . '/preview')) ?>">Xem trước</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/_pagination.php'; ?>
