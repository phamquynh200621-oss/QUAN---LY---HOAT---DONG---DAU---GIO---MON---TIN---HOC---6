<?php
/** Xem trước hoạt động. Biến: $activity (kèm can_select), $selectUrl, $sessionApi, $sessionsBase. */
$a = $activity;
?>
<a href="<?= e($selectUrl) ?>">Quay lại danh sách chọn</a>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mt-2 mb-3">
  <div>
    <h2 class="h4 mb-1"><?= e($a['title']) ?></h2>
    <span class="text-secondary"><?= e($a['topic_name']) ?> / <?= e($a['lesson_name']) ?> / <?= e($a['type_name']) ?></span>
  </div>
  <?php if ($a['can_select']): ?>
    <button type="button" class="btn btn-primary" id="confirm-select"
            data-api="<?= e($sessionApi) ?>" data-activity="<?= (int) $a['id'] ?>" data-base="<?= e($sessionsBase) ?>">Xác nhận chọn hoạt động</button>
  <?php endif; ?>
</div>

<div class="alert d-none" id="page-alert" role="alert"></div>
<?php if (!$a['can_select']): ?>
  <div class="alert alert-info" role="status">
    <?= $a['status'] !== 'approved' ? 'Hoạt động này chưa được duyệt nên chưa chọn được để tổ chức.' : 'Bạn chỉ có quyền xem trước hoạt động.' ?>
  </div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-12 col-xl-8">
    <section class="card mb-4">
      <div class="card-body">
        <?php if ($a['description'] !== ''): ?><p class="lead"><?= nl2br(e($a['description'])) ?></p><?php endif; ?>
        <h3 class="h6">Nội dung, cách tổ chức</h3>
        <p class="mb-0"><?= $a['content'] !== '' ? nl2br(e($a['content'])) : '<span class="text-secondary">Chưa có nội dung.</span>' ?></p>
      </div>
    </section>

    <section class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h3 class="h5 mb-0">Bộ câu hỏi (<?= count($a['questions']) ?>)</h3>
          <?php if ($a['questions'] !== []): ?>
            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" role="switch" id="toggle-answers" data-toggle-answers>
              <label class="form-check-label" for="toggle-answers">Hiện đáp án đúng</label>
            </div>
          <?php endif; ?>
        </div>
        <?php if ($a['questions'] === []): ?><p class="text-secondary mb-0">Hoạt động này chưa có câu hỏi.</p><?php endif; ?>
        <ol class="ps-3 mb-0">
          <?php foreach ($a['questions'] as $q): ?>
            <li class="mb-3">
              <div class="fw-medium"><?= nl2br(e($q['text'])) ?></div>
              <?php foreach ($q['answers'] as $i => $ans): ?>
                <div class="ms-2">
                  <?= e(chr(65 + $i)) ?>. <?= e($ans['text']) ?>
                  <?php if ($ans['is_correct']): ?><span class="badge text-bg-success ms-1 d-none" data-answer-mark>Đúng</span><?php endif; ?>
                </div>
              <?php endforeach; ?>
              <?php if ($q['explanation'] !== ''): ?><div class="small text-secondary d-none" data-answer-mark>Giải thích: <?= e($q['explanation']) ?></div><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </section>
  </div>

  <div class="col-12 col-xl-4">
    <section class="card">
      <div class="card-body">
        <h3 class="h5">Tệp đính kèm</h3>
        <?php if ($a['attachments'] === []): ?><p class="text-secondary mb-0">Không có tệp đính kèm.</p><?php endif; ?>
        <ul class="list-unstyled mb-0">
          <?php foreach ($a['attachments'] as $f): ?>
            <li class="text-break">
              <?php if ($f['exists']): ?><a href="<?= e($f['download_url']) ?>"><?= e($f['name']) ?></a>
              <?php else: ?><?= e($f['name']) ?> <span class="text-danger small">(tệp không còn)</span><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </section>
  </div>
</div>
