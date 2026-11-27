<?php
/**
 * module-user: tạo mới, cấp quyền, mở/khóa tài khoản (dòng 3 của bảng phân quyền).
 * Admin mức Q (toàn quyền); Phó Hiệu trưởng mức C (chỉ xem danh sách). Tổ trưởng và Giáo viên không có quyền.
 * @var Router $router
 */
$view = ['auth', 'permission:3,C'];
$edit = ['auth', 'permission:3,Q'];

// Trang giao diện
$router->get('/admin/users', [UserController::class, 'index'], $view);
$router->get('/admin/roles', [UserController::class, 'roles'], $edit);

// API (JSON)
$router->get('/api/users',                 [UserApiController::class, 'index'],  $view);
$router->get('/api/users/{id}',            [UserApiController::class, 'show'],   $view);
$router->post('/api/users',                [UserApiController::class, 'store'],  $edit);
$router->put('/api/users/{id}',            [UserApiController::class, 'update'], $edit);
$router->patch('/api/users/{id}/status',   [UserApiController::class, 'status'], $edit);
$router->get('/api/roles',                 [UserApiController::class, 'roles'],  $view);
$router->get('/api/permissions/matrix',    [UserApiController::class, 'matrix'], $view);
