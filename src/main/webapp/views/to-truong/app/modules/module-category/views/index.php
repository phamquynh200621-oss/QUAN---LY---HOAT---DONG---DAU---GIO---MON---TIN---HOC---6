<?php
/**
 * Trang danh mục dùng chung.
 * Biến: $def, $items, $filters, $selects, $defaults, $canEdit, $apiPath, $pagePath.
 */
$isLessons = $def['key'] === 'lessons';
$colCount  = count($def['columns']) + 2;   // + cột trạng thái và thao tác
?>
<div id="category-page" data-category
     data-api="<?= e($apiPath) ?>"
     data-singular="<?= e($def['singular']) ?>"
     data-defaults="<?= e(json_encode($defaults, JSON_UNESCAPED_UNICODE)) ?>">

  <div class="alert d-none" id="page-alert" role="alert"></div>

  <div class="d-flex flex-wrap gap-2 align-items-end justify-content-between mb-3">
    <?php if ($isLessons): ?>
      <form method="get" action="<?= e($pagePath) ?>" class="d-flex gap-2 align-items-end">
        <div>
          <label class="form-label mb-1" for="topic_filter">Lọc theo chủ đề</label>
          <select class="form-select" id="topic_filter" name="topic_id" data-autosubmit>
            <option value="">Tất cả chủ đề</option>
            <?php foreach ($selects['topic_id'] as $opt): ?>
              <option value="<?= (int) $opt['id'] ?>"<?= ($filters['topic_id'] ?? null) === (int) $opt['id'] ? ' selected' : '' ?>><?= e($opt['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
    <?php else: ?>
      <p class="text-secondary mb-0"><?= count($items) ?> <?= e($def['singular']) ?></p>
    <?php endif; ?>

    <?php if ($canEdit): ?>
      <button type="button" class="btn btn-primary" data-add>Thêm <?= e($def['singular']) ?></button>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="table-scroll">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <?php foreach ($def['columns'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?>
            <th>Trạng thái</th>
            <?php if ($canEdit): ?><th class="text-end">Thao tác</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if ($items === []): ?>
            <tr><td colspan="<?= $colCount ?>" class="text-center text-secondary py-4">Chưa có <?= e($def['singular']) ?> nào.</td></tr>
          <?php endif; ?>
          <?php foreach ($items as $item): ?>
            <tr data-item="<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>">
              <?php foreach ($def['columns'] as $key => $label): ?>
                <td><?= e((string) ($item[$key] ?? '')) ?></td>
              <?php endforeach; ?>
              <td>
                <?php if ($item['status'] === 'active'): ?>
                  <span class="badge text-bg-success">Hoạt động</span>
                <?php else: ?>
                  <span class="badge text-bg-secondary">Ngừng sử dụng</span>
                <?php endif; ?>
              </td>
              <?php if ($canEdit): ?>
                <td class="text-end text-nowrap">
                  <button type="button" class="btn btn-outline-secondary btn-sm" data-edit>Sửa</button>
                  <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle-status
                          data-next="<?= $item['status'] === 'active' ? 'inactive' : 'active' ?>">
                    <?= $item['status'] === 'active' ? 'Ngừng sử dụng' : 'Kích hoạt' ?>
                  </button>
                  <button type="button" class="btn btn-outline-danger btn-sm" data-delete>Xóa</button>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="category-modal" tabindex="-1" aria-labelledby="category-modal-title" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" novalidate>
      <div class="modal-header">
        <h2 class="modal-title h5" id="category-modal-title"></h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" role="alert" data-form-alert></div>
        <?php foreach ($def['fields'] as $name => $field): ?>
          <div class="mb-3">
            <label class="form-label" for="f-<?= e($name) ?>"><?= e($field['label']) ?></label>
            <?php if ($field['type'] === 'select'): ?>
              <select class="form-select" id="f-<?= e($name) ?>" name="<?= e($name) ?>">
                <option value="">Chọn <?= e(mb_strtolower($field['label'])) ?></option>
                <?php foreach ($selects[$name] as $opt): ?>
                  <option value="<?= (int) $opt['id'] ?>"><?= e($opt['name']) ?><?= $opt['status'] === 'inactive' ? ' (ngừng sử dụng)' : '' ?></option>
                <?php endforeach; ?>
              </select>
            <?php elseif ($field['type'] === 'number'): ?>
              <input type="number" class="form-control" id="f-<?= e($name) ?>" name="<?= e($name) ?>"
                     min="<?= (int) $field['min'] ?>" max="<?= (int) $field['max'] ?>" step="1">
            <?php else: ?>
              <input type="text" class="form-control" id="f-<?= e($name) ?>" name="<?= e($name) ?>">
            <?php endif; ?>
            <div class="invalid-feedback" data-error-for="<?= e($name) ?>"></div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
        <button type="submit" class="btn btn-primary">Lưu</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
