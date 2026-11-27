<?php
/**
 * Danh sách tài khoản.
 * Biến: $items, $filters, $pager, $roles, $classes, $teachingRoles, $canEdit, $selfId, $apiPath, $pagePath, $baseUrl, $categoryUrl.
 */
?>
<div id="user-page" data-user
     data-api="<?= e($apiPath) ?>"
     data-teaching-roles="<?= e(json_encode($teachingRoles)) ?>"
     data-self="<?= (int) $selfId ?>">

  <div class="alert d-none" id="page-alert" role="alert"></div>

  <div class="d-flex flex-wrap gap-2 align-items-end justify-content-between mb-3">
    <form method="get" action="<?= e($pagePath) ?>" class="row g-2 align-items-end">
      <div class="col-auto">
        <label class="form-label mb-1" for="q">Tìm theo tên, tên đăng nhập, email</label>
        <input type="search" class="form-control" id="q" name="q" value="<?= e((string) $filters['q']) ?>" maxlength="100">
      </div>
      <div class="col-auto">
        <label class="form-label mb-1" for="role_filter">Vai trò</label>
        <select class="form-select" id="role_filter" name="role_id">
          <option value="">Tất cả</option>
          <?php foreach ($roles as $r): ?>
            <option value="<?= (int) $r['id'] ?>"<?= $filters['role_id'] === (int) $r['id'] ? ' selected' : '' ?>><?= e($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <label class="form-label mb-1" for="status_filter">Trạng thái</label>
        <select class="form-select" id="status_filter" name="status">
          <option value="">Tất cả</option>
          <option value="active"<?= $filters['status'] === 'active' ? ' selected' : '' ?>>Hoạt động</option>
          <option value="locked"<?= $filters['status'] === 'locked' ? ' selected' : '' ?>>Đã khóa</option>
        </select>
      </div>
      <div class="col-auto d-flex gap-2">
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a class="btn btn-outline-secondary" href="<?= e($pagePath) ?>">Đặt lại</a>
      </div>
    </form>

    <?php if ($canEdit): ?>
      <button type="button" class="btn btn-primary" data-add>Thêm tài khoản</button>
    <?php endif; ?>
  </div>

  <p class="text-secondary mb-2"><?= number_format((int) $pager['total'], 0, ',', '.') ?> tài khoản</p>

  <div class="card">
    <div class="table-scroll">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Họ và tên</th><th>Tên đăng nhập</th><th>Email</th><th>Vai trò</th><th>Lớp dạy</th><th>Đăng nhập gần nhất</th><th>Trạng thái</th>
            <?php if ($canEdit): ?><th class="text-end">Thao tác</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if ($items === []): ?>
            <tr><td colspan="<?= $canEdit ? 8 : 7 ?>" class="text-center text-secondary py-4">Không có tài khoản nào khớp bộ lọc.</td></tr>
          <?php endif; ?>
          <?php foreach ($items as $u): ?>
            <tr data-item="<?= e(json_encode($u, JSON_UNESCAPED_UNICODE)) ?>">
              <td><?= e($u['full_name']) ?><?= $u['id'] === $selfId ? ' <span class="text-secondary">(bạn)</span>' : '' ?></td>
              <td><?= e($u['username']) ?></td>
              <td><?= e($u['email']) ?></td>
              <td><?= e($u['role_name']) ?></td>
              <td><?= e($u['class_codes']) ?></td>
              <td class="text-nowrap"><?= $u['last_login_at'] ? e(date('d/m/Y H:i', strtotime((string) $u['last_login_at']))) : 'Chưa đăng nhập' ?></td>
              <td>
                <?php if ($u['status'] === 'active'): ?>
                  <span class="badge text-bg-success">Hoạt động</span>
                <?php else: ?>
                  <span class="badge text-bg-danger">Đã khóa</span>
                <?php endif; ?>
              </td>
              <?php if ($canEdit): ?>
                <td class="text-end text-nowrap">
                  <button type="button" class="btn btn-outline-secondary btn-sm" data-edit>Sửa</button>
                  <?php if ($u['id'] !== $selfId): ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle-status
                            data-next="<?= $u['status'] === 'active' ? 'locked' : 'active' ?>">
                      <?= $u['status'] === 'active' ? 'Khóa' : 'Mở khóa' ?>
                    </button>
                  <?php endif; ?>
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
<div class="modal fade" id="user-modal" tabindex="-1" aria-labelledby="user-modal-title" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" novalidate>
      <div class="modal-header">
        <h2 class="modal-title h5" id="user-modal-title"></h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" role="alert" data-form-alert></div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label" for="f-username">Tên đăng nhập</label>
            <input type="text" class="form-control" id="f-username" name="username" maxlength="50" autocomplete="off">
            <div class="form-text" data-username-hint>Chữ không dấu, số và . _ - (không đổi được sau khi tạo).</div>
            <div class="invalid-feedback" data-error-for="username"></div>
          </div>
          <div class="col-md-6 mb-3" data-password-group>
            <label class="form-label" for="f-password">Mật khẩu ban đầu</label>
            <input type="password" class="form-control" id="f-password" name="password" autocomplete="new-password" maxlength="72">
            <div class="form-text">Ít nhất 8 ký tự. Người dùng nên đổi sau lần đăng nhập đầu.</div>
            <div class="invalid-feedback" data-error-for="password"></div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label" for="f-full_name">Họ và tên</label>
            <input type="text" class="form-control" id="f-full_name" name="full_name" maxlength="150">
            <div class="invalid-feedback" data-error-for="full_name"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label" for="f-email">Email</label>
            <input type="email" class="form-control" id="f-email" name="email" maxlength="150">
            <div class="invalid-feedback" data-error-for="email"></div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label" for="f-phone">Số điện thoại</label>
            <input type="tel" class="form-control" id="f-phone" name="phone" maxlength="20">
            <div class="invalid-feedback" data-error-for="phone"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label" for="f-role_id">Vai trò</label>
            <select class="form-select" id="f-role_id" name="role_id">
              <option value="">Chọn vai trò</option>
              <?php foreach ($roles as $r): ?>
                <option value="<?= (int) $r['id'] ?>" data-code="<?= e($r['code']) ?>"><?= e($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback" data-error-for="role_id"></div>
          </div>
        </div>

        <fieldset class="mb-1 d-none" data-class-group>
          <legend class="form-label fs-6">Lớp dạy</legend>
          <?php if ($classes === []): ?>
            <p class="text-secondary mb-0">
              Chưa có lớp nào.
              <?php if ($categoryUrl): ?>Hãy tạo lớp ở <a href="<?= e($categoryUrl) ?>">mục Lớp học</a> trước.<?php endif; ?>
            </p>
          <?php else: ?>
            <div class="d-flex flex-wrap gap-3">
              <?php foreach ($classes as $c): ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="class-<?= (int) $c['id'] ?>" value="<?= (int) $c['id'] ?>" data-class-box>
                  <label class="form-check-label" for="class-<?= (int) $c['id'] ?>"><?= e($c['code']) ?><?= $c['status'] === 'inactive' ? ' (ngừng sử dụng)' : '' ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <div class="text-danger small mt-1" data-error-for="class_ids"></div>
        </fieldset>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
        <button type="submit" class="btn btn-primary">Lưu</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
