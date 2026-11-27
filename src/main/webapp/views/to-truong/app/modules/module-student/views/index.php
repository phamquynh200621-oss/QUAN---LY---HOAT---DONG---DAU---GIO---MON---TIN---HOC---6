<?php
/**
 * Danh sách học sinh.
 * Biến: $items, $filters, $pager, $classes, $canEdit, $noClasses, $apiPath, $pagePath, $baseUrl.
 */
$defaultClass = $filters['class_id'] ?? (count($classes) === 1 ? (int) $classes[0]['id'] : '');
$genderLabel  = ['M' => 'Nam', 'F' => 'Nữ'];
?>
<div id="student-page" data-student
     data-api="<?= e($apiPath) ?>"
     data-default-class="<?= e((string) $defaultClass) ?>">

  <div class="alert d-none" id="page-alert" role="alert"></div>

  <?php if ($noClasses): ?>
    <div class="alert alert-warning" role="alert">
      Bạn chưa được phân công lớp dạy nên chưa thấy hoặc thêm được học sinh. Hãy nhờ quản trị viên gán lớp cho tài khoản của bạn.
    </div>
  <?php endif; ?>

  <div class="d-flex flex-wrap gap-2 align-items-end justify-content-between mb-3">
    <form method="get" action="<?= e($pagePath) ?>" class="row g-2 align-items-end">
      <div class="col-auto">
        <label class="form-label mb-1" for="q">Tìm theo mã hoặc tên</label>
        <input type="search" class="form-control" id="q" name="q" value="<?= e((string) $filters['q']) ?>" maxlength="100">
      </div>
      <div class="col-auto">
        <label class="form-label mb-1" for="class_filter">Lớp</label>
        <select class="form-select" id="class_filter" name="class_id">
          <option value="">Tất cả lớp</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?= (int) $c['id'] ?>"<?= $filters['class_id'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['code']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto d-flex gap-2">
        <button type="submit" class="btn btn-primary">Tìm</button>
        <a class="btn btn-outline-secondary" href="<?= e($pagePath) ?>">Đặt lại</a>
      </div>
    </form>

    <?php if ($canEdit): ?>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" data-import>Nhập từ CSV</button>
        <button type="button" class="btn btn-primary" data-add>Thêm học sinh</button>
      </div>
    <?php endif; ?>
  </div>

  <p class="text-secondary mb-2"><?= number_format((int) $pager['total'], 0, ',', '.') ?> học sinh</p>

  <div class="card">
    <div class="table-scroll">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Mã học sinh</th><th>Họ và tên</th><th>Ngày sinh</th><th>Giới tính</th><th>Lớp</th><th>Trạng thái</th>
            <?php if ($canEdit): ?><th class="text-end">Thao tác</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if ($items === []): ?>
            <tr><td colspan="<?= $canEdit ? 7 : 6 ?>" class="text-center text-secondary py-4">Không có học sinh nào.</td></tr>
          <?php endif; ?>
          <?php foreach ($items as $s): ?>
            <tr data-item="<?= e(json_encode($s, JSON_UNESCAPED_UNICODE)) ?>">
              <td><?= e($s['student_code']) ?></td>
              <td><?= e($s['full_name']) ?></td>
              <td class="text-nowrap"><?= $s['dob'] ? e(date('d/m/Y', strtotime((string) $s['dob']))) : '' ?></td>
              <td><?= e($genderLabel[$s['gender']] ?? '') ?></td>
              <td><?= e($s['class_code']) ?></td>
              <td>
                <?php if ($s['status'] === 'active'): ?>
                  <span class="badge text-bg-success">Đang học</span>
                <?php else: ?>
                  <span class="badge text-bg-secondary">Ngừng sử dụng</span>
                <?php endif; ?>
              </td>
              <?php if ($canEdit): ?>
                <td class="text-end text-nowrap">
                  <button type="button" class="btn btn-outline-secondary btn-sm" data-edit>Sửa</button>
                  <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle-status
                          data-next="<?= $s['status'] === 'active' ? 'inactive' : 'active' ?>">
                    <?= $s['status'] === 'active' ? 'Ngừng sử dụng' : 'Kích hoạt' ?>
                  </button>
                  <button type="button" class="btn btn-outline-danger btn-sm" data-delete>Xóa</button>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php require __DIR__ . '/_pagination.php'; ?>
</div>

<?php if ($canEdit): ?>
<!-- Hộp thoại thêm / sửa -->
<div class="modal fade" id="student-modal" tabindex="-1" aria-labelledby="student-modal-title" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" novalidate>
      <div class="modal-header">
        <h2 class="modal-title h5" id="student-modal-title"></h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" role="alert" data-form-alert></div>
        <div class="mb-3">
          <label class="form-label" for="f-student_code">Mã học sinh</label>
          <input type="text" class="form-control" id="f-student_code" name="student_code" maxlength="30">
          <div class="invalid-feedback" data-error-for="student_code"></div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="f-full_name">Họ và tên</label>
          <input type="text" class="form-control" id="f-full_name" name="full_name" maxlength="150">
          <div class="invalid-feedback" data-error-for="full_name"></div>
        </div>
        <div class="row">
          <div class="col-sm-6 mb-3">
            <label class="form-label" for="f-dob">Ngày sinh</label>
            <input type="date" class="form-control" id="f-dob" name="dob" max="<?= e(date('Y-m-d')) ?>">
            <div class="invalid-feedback" data-error-for="dob"></div>
          </div>
          <div class="col-sm-6 mb-3">
            <label class="form-label" for="f-gender">Giới tính</label>
            <select class="form-select" id="f-gender" name="gender">
              <option value="">Chưa chọn</option>
              <option value="M">Nam</option>
              <option value="F">Nữ</option>
            </select>
            <div class="invalid-feedback" data-error-for="gender"></div>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="f-class_id">Lớp</label>
          <select class="form-select" id="f-class_id" name="class_id">
            <option value="">Chọn lớp</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= (int) $c['id'] ?>"><?= e($c['code']) ?> - <?= e($c['name']) ?><?= $c['status'] === 'inactive' ? ' (ngừng sử dụng)' : '' ?></option>
            <?php endforeach; ?>
          </select>
          <div class="invalid-feedback" data-error-for="class_id"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
        <button type="submit" class="btn btn-primary">Lưu</button>
      </div>
    </form>
  </div>
</div>

<!-- Hộp thoại nhập CSV -->
<div class="modal fade" id="import-modal" tabindex="-1" aria-labelledby="import-modal-title" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" novalidate>
      <div class="modal-header">
        <h2 class="modal-title h5" id="import-modal-title">Nhập danh sách học sinh từ CSV</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">
        <p class="text-secondary">Tệp CSV (UTF-8) có dòng đầu là tiêu đề <code>student_code, full_name, dob, gender</code>.
          Ngày sinh dạng 2014-05-20 hoặc 20/05/2014; giới tính ghi Nam hoặc Nữ. Tối đa 500 dòng.
          <a href="<?= e(url('/students/template')) ?>">Tải tệp mẫu</a></p>
        <div class="alert d-none" role="alert" data-form-alert></div>
        <div class="mb-3">
          <label class="form-label" for="i-class_id">Nhập vào lớp</label>
          <select class="form-select" id="i-class_id" name="class_id">
            <option value="">Chọn lớp</option>
            <?php foreach ($classes as $c): ?>
              <?php if ($c['status'] === 'active'): ?>
                <option value="<?= (int) $c['id'] ?>"><?= e($c['code']) ?> - <?= e($c['name']) ?></option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
          <div class="invalid-feedback" data-error-for="class_id"></div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="i-file">Tệp CSV</label>
          <input type="file" class="form-control" id="i-file" name="file" accept=".csv,.txt,text/csv">
          <div class="invalid-feedback" data-error-for="file"></div>
        </div>
        <ul class="small text-danger ps-3 mb-0 d-none" data-import-errors></ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
        <button type="submit" class="btn btn-primary">Nhập</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
