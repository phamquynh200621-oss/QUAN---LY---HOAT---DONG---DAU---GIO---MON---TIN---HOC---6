<?php /** Form đăng nhập (MDI1.1). Gửi bằng AJAX tới /api/auth/login (xem assets/js/auth.js). */ ?>
<h2>Đăng nhập</h2>

<div class="alert d-none" role="alert" data-form-alert></div>

<form data-api="<?= e(url('/api/auth/login')) ?>" novalidate>
  <div class="mb-3">
    <label class="form-label" for="login">Tên đăng nhập hoặc email</label>
    <input type="text" class="form-control" id="login" name="login" autocomplete="username" autofocus required>
    <div class="invalid-feedback" data-error-for="login"></div>
  </div>
  <div class="mb-3">
    <label class="form-label" for="password">Mật khẩu</label>
    <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
    <div class="invalid-feedback" data-error-for="password"></div>
  </div>
  <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
</form>

<p class="mt-3 mb-0 text-center"><a href="<?= e(url('/forgot-password')) ?>">Quên mật khẩu?</a></p>
