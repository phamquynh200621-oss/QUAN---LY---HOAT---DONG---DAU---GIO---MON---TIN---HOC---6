<?php
/**
 * module-auth: đăng nhập, đăng xuất, quên và đặt lại mật khẩu (dòng 1 của bảng phân quyền).
 * @var Router $router
 */

// Trang giao diện
$router->get('/login',           [AuthController::class,          'showLogin'],  ['guest']);
$router->post('/logout',         [AuthController::class,          'logout'],     ['auth']);
$router->get('/forgot-password', [PasswordResetController::class, 'showForgot'], ['guest']);
$router->get('/reset-password',  [PasswordResetController::class, 'showReset'],  ['guest']);

// API (JSON)
$router->post('/api/auth/login',           [AuthApiController::class, 'login']);
$router->post('/api/auth/logout',          [AuthApiController::class, 'logout'],         ['auth']);
$router->post('/api/auth/forgot-password', [AuthApiController::class, 'forgotPassword']);
$router->post('/api/auth/reset-password',  [AuthApiController::class, 'resetPassword']);
