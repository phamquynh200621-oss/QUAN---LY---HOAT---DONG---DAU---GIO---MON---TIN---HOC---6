<?php
/**
 * API JSON dùng chung cho 4 danh mục (URL theo mục 6 của kế hoạch):
 *   GET    /api/{classes|topics|lessons|activity-types}            (lessons: ?topic_id=)
 *   POST   /api/.../              body: các trường của danh mục
 *   PUT    /api/.../{id}
 *   PATCH  /api/.../{id}/status   {status: active|inactive}
 *   DELETE /api/.../{id}          409 nếu còn dữ liệu liên quan
 * Quyền kiểm tra bằng middleware ở routes.php (xem = C, ghi = T).
 */
final class CategoryApiController extends Controller
{
    public function index(array $params): void
    {
        $def     = $this->definition($params);
        $filters = [];
        foreach ($def['filters'] as $column) {
            $value = $this->request->string($column);
            if ($value !== '' && ctype_digit($value) && (int) $value > 0) {
                $filters[$column] = (int) $value;
            }
        }
        $items = Category::listAll($def, $filters);
        $this->success(['items' => $items, 'total' => count($items)]);
    }

    public function store(array $params): void
    {
        $this->respond(CategoryService::create($this->definition($params), $this->request->all()));
    }

    public function update(array $params): void
    {
        $this->respond(CategoryService::update($this->definition($params), $this->id($params), $this->request->all()));
    }

    public function status(array $params): void
    {
        $this->respond(CategoryService::setStatus($this->definition($params), $this->id($params), $this->request->string('status')));
    }

    public function destroy(array $params): void
    {
        $this->respond(CategoryService::delete($this->definition($params), $this->id($params)));
    }

    /** @return array<string,mixed> */
    private function definition(array $params): array
    {
        $def = CategoryDefinition::get((string) ($params['resource'] ?? ''));
        if ($def === null) {
            $this->error('Không tìm thấy danh mục.', 404);
        }
        return $def;
    }

    private function id(array $params): int
    {
        $id = (string) ($params['id'] ?? '');
        if (!ctype_digit($id) || (int) $id < 1) {
            $this->error('Mã không hợp lệ.', 404);
        }
        return (int) $id;
    }

    /** @param array<string,mixed> $result kết quả của CategoryService */
    private function respond(array $result): void
    {
        if (!$result['ok']) {
            $this->error($result['message'], $result['status'], $result['errors']);
        }
        $this->success($result['item'], $result['message'], $result['status']);
    }
}
