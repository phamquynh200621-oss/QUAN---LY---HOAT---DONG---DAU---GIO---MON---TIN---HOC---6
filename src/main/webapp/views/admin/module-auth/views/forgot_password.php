<?php /** Form yêu cầu liên kết đặt lại mật khẩu. */ ?>
<h2>Quên mật khẩu</h2>
<p class="text-secondary">Nhập email của tài khoản. Hệ thống sẽ gửi liên kết để bạn đặt mật khẩu mới.</p>

<div class="alert d-none" role="alert" data-form-alert></div>

<form data-api="<?= e(url('/api/auth/forgot-password')) ?>" data-reset-on-success novalidate>
  <div class="mb-3">
    <label class="form-label" for="email">Email</label>
    <input type="email" class="form-control" id="email" name="email" autocomplete="email" autofocus required>
    <div class="invalid-feedback" data-error-for="email"></div>
  </div>
  <button type="submit" class="btn btn-primary w-100">Gửi liên kết đặt lại</button>
</form>

<p class="mt-3 mb-0 text-center"><a href="<?= e(url('/login')) ?>">Quay lại đăng nhập</a></p>
