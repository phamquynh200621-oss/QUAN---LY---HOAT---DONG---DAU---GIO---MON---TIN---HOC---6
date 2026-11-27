<?php
/**
 * module-profile: xem và cập nhật thông tin cá nhân (dòng 2 của bảng phân quyền: mọi vai trò mức T).
 * @var Router $router
 */
$guard = ['auth', 'permission:2,T'];

// Trang giao diện
$router->get('/profile',        [ProfileController::class, 'index'],  $guard);
$router->get('/profile/avatar', [ProfileController::class, 'avatar'], $guard);

// API (JSON)
$router->get('/api/profile',           [ProfileApiController::class, 'show'],           $guard);
$router->put('/api/profile',           [ProfileApiController::class, 'update'],         $guard);
$router->put('/api/profile/password',  [ProfileApiController::class, 'changePassword'], $guard);
$router->post('/api/profile/avatar',   [ProfileApiController::class, 'uploadAvatar'],   $guard);
