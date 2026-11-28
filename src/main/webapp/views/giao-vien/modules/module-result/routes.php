<?php
/**
 * module-result: ghi nhận điểm / đánh giá học sinh và xem lại lịch sử (dòng 16, 17; MDVI1, MDVI2.2).
 *  - Ghi nhận (dòng 16): chỉ Tổ trưởng và Giáo viên, trên phiên do chính mình tổ chức, cho học sinh của lớp trong phiên.
 *  - Xem lịch sử (dòng 17): Admin, Phó Hiệu trưởng, Tổ trưởng xem tất cả; Giáo viên chỉ xem lớp mình dạy.
 * Thống kê theo lớp/khối (dòng 18) thuộc module-report.
 * @var Router $router
 */
$record = ['auth', 'permission:16,T'];
$view   = ['auth', 'permission:17,C'];

// Trang giao diện
$router->get('/sessions/{id:\d+}/results', [ResultController::class, 'sheet'], $record);
$router->get('/results',                   [ResultController::class, 'index'], $view);

// API (JSON)
$router->put('/api/sessions/{id:\d+}/results',          [ResultApiController::class, 'save'],     $record);
$router->get('/api/sessions/{id:\d+}/results/status',   [ResultApiController::class, 'status'],   $record);
$router->post('/api/sessions/{id:\d+}/results/save',    [ResultApiController::class, 'finalize'], $record);
$router->get('/api/results',                            [ResultApiController::class, 'index'],    $view);
$router->get('/api/results/{id:\d+}/history',           [ResultApiController::class, 'history'],  $view);
