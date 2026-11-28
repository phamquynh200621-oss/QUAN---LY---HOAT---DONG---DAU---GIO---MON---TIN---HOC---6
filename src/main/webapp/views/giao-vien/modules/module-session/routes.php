<?php
/**
 * module-session: chọn, xem trước, thiết lập, điều khiển và trình chiếu hoạt động cho lớp (dòng 14, 15; MDV).
 *  - Xem trước / chọn (dòng 14): Admin, Phó Hiệu trưởng chỉ xem trước (C); Tổ trưởng, Giáo viên chọn được (T).
 *  - Thiết lập, điều khiển, trình chiếu (dòng 15): chỉ Tổ trưởng và Giáo viên, và chỉ trên phiên do chính mình tạo.
 * Luồng: /sessions/select -> /activities/{id}/preview -> xác nhận chọn -> /sessions/{id}/config
 *        -> /sessions/{id} (điều khiển) + /sessions/{id}/present (màn hình chiếu mở ở cửa sổ riêng).
 * @var Router $router
 */
$preview = ['auth', 'permission:14,C'];
$select  = ['auth', 'permission:14,T'];
$run     = ['auth', 'permission:15,T'];

// Trang giao diện
$router->get('/sessions/select',                 [SessionController::class, 'select'],  $preview);
$router->get('/activities/{id:\d+}/preview',     [SessionController::class, 'preview'], $preview);
$router->get('/sessions',                        [SessionController::class, 'mine'],    $run);
$router->get('/sessions/{id:\d+}/config',        [SessionController::class, 'config'],  $run);
$router->get('/sessions/{id:\d+}/present',       [SessionController::class, 'present'], $run);
$router->get('/sessions/{id:\d+}',               [SessionController::class, 'control'], $run);

// API (JSON)
$router->get('/api/activities/{id:\d+}/preview', [SessionApiController::class, 'preview'], $preview);
$router->post('/api/sessions',                   [SessionApiController::class, 'create'],  $select);
$router->get('/api/sessions',                    [SessionApiController::class, 'index'],   $run);
$router->put('/api/sessions/{id:\d+}/config',    [SessionApiController::class, 'config'],  $run);
$router->post('/api/sessions/{id:\d+}/control',  [SessionApiController::class, 'control'], $run);
$router->get('/api/sessions/{id:\d+}/state',     [SessionApiController::class, 'state'],   $run);
