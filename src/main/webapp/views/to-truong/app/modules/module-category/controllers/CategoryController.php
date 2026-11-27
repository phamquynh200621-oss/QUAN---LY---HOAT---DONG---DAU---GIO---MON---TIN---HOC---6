<?php
/** Trang danh sách danh mục (dùng chung cho 4 loại; loại nào do route truyền vào $params['resource']). */
final class CategoryController extends Controller
{
    public function index(array $params): void
    {
        $def = CategoryDefinition::get((string) ($params['resource'] ?? ''));
        if ($def === null) {
            Response::abort(404, 'Không tìm thấy danh mục.', false);
        }

        // Bộ lọc theo cột (ví dụ bài học lọc theo chủ đề)
        $filters = [];
        foreach ($def['filters'] as $column) {
            $value = $this->request->string($column);
            if ($value !== '' && ctype_digit($value) && (int) $value > 0) {
                $filters[$column] = (int) $value;
            }
        }

        // Ô chọn tham chiếu (ví dụ danh sách chủ đề trong form bài học) và giá trị mặc định của form thêm mới
        $selects  = [];
        $defaults = [];
        foreach ($def['fields'] as $name => $field) {
            if ($field['type'] === 'select') {
                $selects[$name]  = Category::options(CategoryDefinition::get($field['source']));
                $defaults[$name] = $filters[$name] ?? '';
            } elseif ($field['type'] === 'number') {
                $defaults[$name] = $field['default'] ?? 0;
            } else {
                $defaults[$name] = '';
            }
        }

        $this->view('module-category::index', [
            'title'    => $def['title'],
            'def'      => $def,
            'items'    => Category::listAll($def, $filters),
            'filters'  => $filters,
            'selects'  => $selects,
            'defaults' => $defaults,
            'canEdit'  => Permission::can((int) $def['function'], 'T'),
            'apiPath'  => url(CategoryDefinition::apiPath($def)),
            'pagePath' => url(CategoryDefinition::pagePath($def)),
            'scripts'  => [module_asset('category', 'js/category.js')],
        ]);
    }
}
