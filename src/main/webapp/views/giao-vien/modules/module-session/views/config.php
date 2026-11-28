<?php
/** Thiết lập thông số phiên. Biến: $session, $classes, $questionTotal, $editable, $controlUrl, $apiUrl, $badges, $statusLabel. */
$s = $session;
?>
<div class="mb-3">
  <h2 class="h4 mb-1"><?= e($s['activity_title']) ?></h2>
  <span class="badge <?= e($badges[$s['status']] ?? 'text-bg-secondary') ?>"><?= e($statusLabel) ?></span>
  <span class="text-secondary ms-2"><?= e($s['type_name']) ?></span>
</div>

<?php if (!$editable): ?>
  <div class="alert alert-info" role="status">
    Phiên đã bắt đầu nên không đổi được thông số. <a href="<?= e($controlUrl) ?>">Về bảng điều khiển</a>.
  </div>
<?php endif; ?>

<?php if ($classes === []): ?>
  <div class="alert alert-warning" role="alert">Bạn chưa được phân công lớp dạy nào. Hãy nhờ Admin gán lớp cho tài khoản của bạn.</div>
<?php endif; ?>

<form id="config-form" class="card" novalidate data-api="<?= e($apiUrl) ?>" data-next="<?= e($controlUrl) ?>">
  <div class="card-body">
    <div class="alert alert-danger d-none" role="alert" data-form-alert></div>
    <fieldset<?= $editable ? '' : ' disabled' ?>>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label" for="class_id">Lớp</label>
          <select class="form-select" id="class_id" name="class_id">
            <option value="">Chọn lớp</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= (int) $c['id'] ?>"<?= (int) $s['class_id'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['code']) ?> - <?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="invalid-feedback" data-error-for="class_id"></div>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label" for="duration_minutes">Thời gian (phút)</label>
          <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" min="1" max="180" step="1"
                 value="<?= (int) ($s['duration_minutes'] ?: 5) ?>">
          <div class="invalid-feedback" data-error-for="duration_minutes"></div>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label" for="question_count">Số câu hỏi</label>
          <input type="number" class="form-control" id="question_count" name="question_count" min="0" max="<?= (int) $questionTotal ?>" step="1"
                 value="<?= (int) $s['question_count'] ?>"<?= $questionTotal === 0 ? ' readonly' : '' ?>>
          <div class="form-text"><?= $questionTotal === 0 ? 'Hoạt động chưa có câu hỏi.' : "0 là dùng tất cả {$questionTotal} câu." ?></div>
          <div class="invalid-feedback" data-error-for="question_count"></div>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="play_mode">Cách chơi</label>
        <input type="text" class="form-control" id="play_mode" name="play_mode" maxlength="255" list="play-modes"
               value="<?= e((string) $s['play_mode']) ?>" placeholder="Ví dụ: Cả lớp cùng trả lời, chia nhóm thi đua">
        <datalist id="play-modes">
          <option value="Cả lớp cùng trả lời"></option>
          <option value="Chia nhóm thi đua"></option>
          <option value="Từng học sinh lên bảng"></option>
        </datalist>
        <div class="invalid-feedback" data-error-for="play_mode"></div>
      </div>
    </fieldset>
  </div>
  <div class="card-footer d-flex gap-2">
    <?php if ($editable): ?><button type="submit" class="btn btn-primary"<?= $classes === [] ? ' disabled' : '' ?>>Lưu và vào điều khiển</button><?php endif; ?>
    <a class="btn btn-outline-secondary" href="<?= e(url('/sessions')) ?>">Quay lại</a>
  </div>
</form>
