<?php
/** Trang hồ sơ cá nhân. Biến: $profile (xem ProfileService::present). */
$initial = mb_strtoupper(mb_substr(trim((string) $profile['full_name']), 0, 1));
$lastLogin = $profile['last_login_at'] ? date('d/m/Y H:i', strtotime((string) $profile['last_login_at'])) : 'Chưa có';
?>
<div class="row g-4">
  <div class="col-12 col-xl-7">
    <section class="card profile-card">
      <div class="card-body">
        <h2 class="h5 mb-3">Thông tin cá nhân</h2>

        <div class="d-flex align-items-center gap-3 mb-4">
          <div class="avatar" data-avatar>
            <?php if ($profile['avatar_url']): ?>
              <img src="<?= e($profile['avatar_url']) ?>" alt="Ảnh đại diện">
            <?php else: ?>
              <span class="avatar-fallback" aria-hidden="true"><?= e($initial) ?></span>
            <?php endif; ?>
          </div>
          <form data-api="<?= e(url('/api/profile/avatar')) ?>" data-multipart data-auto-submit>
            <label class="btn btn-outline-secondary btn-sm mb-1" for="avatar">Đổi ảnh đại diện</label>
            <input type="file" class="visually-hidden" id="avatar" name="avatar" accept="image/png,image/jpeg,image/webp">
            <div class="form-text">JPG, PNG hoặc WebP, tối đa 2 MB.</div>
            <div class="alert d-none mt-2 mb-0 py-2" role="alert" data-form-alert></div>
          </form>
        </div>

        <dl class="row mb-4">
          <dt class="col-sm-4 fw-medium">Tên đăng nhập</dt><dd class="col-sm-8"><?= e($profile['username']) ?></dd>
          <dt class="col-sm-4 fw-medium">Vai trò</dt><dd class="col-sm-8"><?= e($profile['role_name']) ?></dd>
          <dt class="col-sm-4 fw-medium">Đăng nhập gần nhất</dt><dd class="col-sm-8"><?= e($lastLogin) ?></dd>
        </dl>

        <form data-api="<?= e(url('/api/profile')) ?>" data-method="PUT" novalidate>
          <div class="alert d-none" role="alert" data-form-alert></div>
          <div class="mb-3">
            <label class="form-label" for="full_name">Họ và tên</label>
            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($profile['full_name']) ?>" maxlength="150" required>
            <div class="invalid-feedback" data-error-for="full_name"></div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= e($profile['email']) ?>" maxlength="150" required>
            <div class="invalid-feedback" data-error-for="email"></div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="phone">Số điện thoại</label>
            <input type="tel" class="form-control" id="phone" name="phone" value="<?= e((string) $profile['phone']) ?>" maxlength="20">
            <div class="invalid-feedback" data-error-for="phone"></div>
          </div>
          <button type="submit" class="btn btn-primary">Lưu thông tin</button>
        </form>
      </div>
    </section>
  </div>

  <div class="col-12 col-xl-5">
    <section class="card profile-card">
      <div class="card-body">
        <h2 class="h5 mb-3">Đổi mật khẩu</h2>
        <form data-api="<?= e(url('/api/profile/password')) ?>" data-method="PUT" data-reset-on-success novalidate>
          <div class="alert d-none" role="alert" data-form-alert></div>
          <div class="mb-3">
            <label class="form-label" for="old_password">Mật khẩu hiện tại</label>
            <input type="password" class="form-control" id="old_password" name="old_password" autocomplete="current-password" required>
            <div class="invalid-feedback" data-error-for="old_password"></div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="new_password">Mật khẩu mới</label>
            <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password" minlength="8" required>
            <div class="form-text">Ít nhất 8 ký tự.</div>
            <div class="invalid-feedback" data-error-for="new_password"></div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="new_password_confirm">Nhập lại mật khẩu mới</label>
            <input type="password" class="form-control" id="new_password_confirm" name="new_password_confirm" autocomplete="new-password" required>
            <div class="invalid-feedback" data-error-for="new_password_confirm"></div>
          </div>
          <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
        </form>
      </div>
    </section>
  </div>
</div>
