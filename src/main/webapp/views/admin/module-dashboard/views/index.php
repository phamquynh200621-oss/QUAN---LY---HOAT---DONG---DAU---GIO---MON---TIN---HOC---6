<?php /** BẢN TẠM của trang tổng quan. Biến: $user. */ ?>
<h2 class="h4">Xin chào, <?= e($user['full_name']) ?></h2>
<p class="text-secondary">Bạn đăng nhập với vai trò <strong><?= e($user['role_name']) ?></strong>.
  Các mục trong menu bên trái sẽ xuất hiện dần khi từng module được hoàn thành.</p>
