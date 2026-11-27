<?php
/**
 * module-log: xem nhật ký (chỉ đọc).
 *  - Nhật ký sao lưu/phục hồi: dòng 23 (Admin Q, Phó Hiệu trưởng C) - MDVII2.2
 *  - Nhật ký hoạt động hệ thống: dòng 25 (chỉ Admin)
 * Việc GHI nhật ký do lớp Logger (app/core) đảm nhiệm; module-backup sẽ ghi bảng backup_logs.
 * @var Router $router
 */
$systemGuard = ['auth', 'permission:25,Q'];
$backupGuard = ['auth', 'permission:23,C'];

// Trang giao diện
$router->get('/logs/system',  [LogController::class, 'system'],  $systemGuard);
$router->get('/logs/backups', [LogController::class, 'backups'], $backupGuard);

// API (JSON)
$router->get('/api/logs/system',  [LogApiController::class, 'system'],  $systemGuard);
$router->get('/api/logs/backups', [LogApiController::class, 'backups'], $backupGuard);
