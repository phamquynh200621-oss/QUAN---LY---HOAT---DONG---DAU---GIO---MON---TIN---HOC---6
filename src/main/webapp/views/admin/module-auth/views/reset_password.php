<?php /** Form đặt mật khẩu mới. Biến: $token, $tokenValid. */ ?>
<h2>Đặt mật khẩu mới</h2>

<?php if (!$tokenValid): ?>
  <div class="alert alert-danger" role="alert"><?= e(PasswordResetService::MSG_INVALID) ?></div>
  <a class="btn btn-primary w-100" href="<?= e(url('/forgot-password')) ?>">Yêu cầu liên kết mới</a>
<?php else: ?>
  <div class="alert d-none" role="alert" data-form-alert></div>

  <form data-api="<?= e(url('/api/auth/reset-password')) ?>" novalidate>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <div class="mb-3">
      <label class="form-label" for="password">Mật khẩu mới</label>
      <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" minlength="8" autofocus required>
      <div class="form-text">Ít nhất 8 ký tự.</div>
      <div class="invalid-feedback" data-error-for="password"></div>
    </div>
    <div class="mb-3">
      <label class="form-label" for="password_confirm">Nhập lại mật khẩu mới</label>
      <input type="password" class="form-control" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
      <div class="invalid-feedback" data-error-for="password_confirm"></div>
    </div>
    <button type="submit" class="btn btn-primary w-100">Lưu mật khẩu mới</button>
  </form>
<?php endif; ?>
