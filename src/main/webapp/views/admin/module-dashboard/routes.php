<?php
/**
 * module-dashboard (BẢN TẠM do module-auth tạo để có trang đích sau khi đăng nhập).
 * Thành viên phụ trách module-dashboard sẽ thay nội dung controller và view.
 * @var Router $router
 */
$router->get('/dashboard', [DashboardController::class, 'index'], ['auth']);
