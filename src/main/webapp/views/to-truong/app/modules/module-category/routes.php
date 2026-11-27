<?php
/**
 * module-category: danh mục dùng chung (dòng 4, 5, 6 của bảng phân quyền).
 *   /categories/classes          lớp học          (dòng 4)
 *   /categories/topics           chủ đề           (dòng 5)
 *   /categories/lessons          bài học          (dòng 5)
 *   /categories/activity-types   hình thức hoạt động (dòng 6)
 * Bốn danh mục dùng chung một bộ controller/service/view; khác nhau ở định nghĩa
 * trong services/CategoryDefinition.php. Xem = mức C, thêm/sửa/xóa = mức T.
 * @var Router $router
 */
$resources = [
    'classes'        => ['/categories/classes',        '/api/classes'],
    'topics'         => ['/categories/topics',         '/api/topics'],
    'lessons'        => ['/categories/lessons',        '/api/lessons'],
    'activity-types' => ['/categories/activity-types', '/api/activity-types'],
];

foreach ($resources as $key => [$pagePath, $apiPath]) {
    $fn   = CategoryDefinition::get($key)['function'];
    $view = ['auth', "permission:{$fn},C"];
    $edit = ['auth', "permission:{$fn},T"];
    $with = ['resource' => $key];

    // Trang giao diện
    $router->get($pagePath, [CategoryController::class, 'index'], $view, $with);

    // API (JSON)
    $router->get($apiPath,                  [CategoryApiController::class, 'index'],  $view, $with);
    $router->post($apiPath,                 [CategoryApiController::class, 'store'],  $edit, $with);
    $router->put($apiPath . '/{id}',        [CategoryApiController::class, 'update'], $edit, $with);
    $router->patch($apiPath . '/{id}/status', [CategoryApiController::class, 'status'], $edit, $with);
    $router->delete($apiPath . '/{id}',     [CategoryApiController::class, 'destroy'], $edit, $with);
}
