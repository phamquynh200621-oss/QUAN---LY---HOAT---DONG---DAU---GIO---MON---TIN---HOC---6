<?php
/**
 * module-student: danh sách học sinh (dòng 24 - chức năng bổ sung theo yêu cầu của nhóm).
 * Admin toàn quyền; Tổ trưởng và Giáo viên thao tác trong lớp mình dạy (phạm vi 'class'); Phó Hiệu trưởng chỉ xem.
 * @var Router $router
 */
$view = ['auth', 'permission:24,C'];
$edit = ['auth', 'permission:24,T'];

// Trang giao diện
$router->get('/students',          [StudentController::class, 'index'],    $view);
$router->get('/students/template', [StudentController::class, 'template'], $edit);

// API (JSON)
$router->get('/api/students',                 [StudentApiController::class, 'index'],   $view);
$router->post('/api/students',                [StudentApiController::class, 'store'],   $edit);
$router->post('/api/students/import',         [StudentApiController::class, 'import'],  $edit);
$router->put('/api/students/{id}',            [StudentApiController::class, 'update'],  $edit);
$router->patch('/api/students/{id}/status',   [StudentApiController::class, 'status'],  $edit);
$router->delete('/api/students/{id}',         [StudentApiController::class, 'destroy'], $edit);
