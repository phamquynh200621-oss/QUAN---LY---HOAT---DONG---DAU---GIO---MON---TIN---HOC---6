<?php
/** Nhật ký sao lưu và phục hồi. Biến: $items, $filters, $pager, $baseUrl. */
?>
<form method="get" action="<?= e($baseUrl) ?>" class="row g-2 align-items-end mb-3">
  <div class="col-6 col-md-3 col-xl-2">
    <label class="form-label" for="from">Từ ngày</label>
    <input type="date" class="form-control" id="from" name="from" value="<?= e((string) $filters['from']) ?>">
  </div>
  <div class="col-6 col-md-3 col-xl-2">
    <label class="form-label" for="to">Đến ngày</label>
    <input type="date" class="form-control" id="to" name="to" value="<?= e((string) $filters['to']) ?>">
  </div>
  <div class="col-6 col-md-3 col-xl-2">
    <label class="form-label" for="operation">Loại thao tác</label>
    <select class="form-select" id="operation" name="operation">
      <option value="">Tất cả</option>
      <option value="backup"<?= $filters['operation'] === 'backup' ? ' selected' : '' ?>>Sao lưu</option>
      <option value="restore"<?= $filters['operation'] === 'restore' ? ' selected' : '' ?>>Phục hồi</option>
    </select>
  </div>
  <div class="col-6 col-md-3 col-xl-2">
    <label class="form-label" for="status">Trạng thái</label>
    <select class="form-select" id="status" name="status">
      <option value="">Tất cả</option>
      <option value="success"<?= $filters['status'] === 'success' ? ' selected' : '' ?>>Thành công</option>
      <option value="failed"<?= $filters['status'] === 'failed' ? ' selected' : '' ?>>Thất bại</option>
    </select>
  </div>
  <div class="col-12 col-xl-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">Lọc</button>
    <a class="btn btn-outline-secondary" href="<?= e($baseUrl) ?>">Đặt lại</a>
  </div>
</form>

<p class="text-secondary mb-2"><?= number_format((int) $pager['total'], 0, ',', '.') ?> bản ghi</p>

<div class="card">
  <div class="table-scroll">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr><th>Bắt đầu</th><th>Kết thúc</th><th>Thao tác</th><th>Tệp sao lưu</th><th>Dung lượng</th><th>Người thực hiện</th><th>Trạng thái</th><th>Ghi chú</th></tr>
      </thead>
      <tbody>
        <?php if ($items === []): ?>
          <tr><td colspan="8" class="text-center text-secondary py-4">Chưa có bản ghi sao lưu hoặc phục hồi nào.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $row): ?>
          <tr>
            <td class="text-nowrap"><?= e(date('d/m/Y H:i:s', strtotime((string) $row['started_at']))) ?></td>
            <td class="text-nowrap"><?= $row['finished_at'] ? e(date('d/m/Y H:i:s', strtotime((string) $row['finished_at']))) : '' ?></td>
            <td><?= $row['operation'] === 'restore' ? 'Phục hồi' : 'Sao lưu' ?></td>
            <td><?= e((string) $row['file_name']) ?></td>
            <td class="text-nowrap"><?= e(LogService::formatBytes($row['file_size'] === null ? null : (int) $row['file_size'])) ?></td>
            <td><?= $row['full_name'] !== null ? e($row['full_name']) : '<span class="text-secondary">Tự động</span>' ?></td>
            <td>
              <?php if ($row['status'] === 'success'): ?>
                <span class="badge text-bg-success">Thành công</span>
              <?php else: ?>
                <span class="badge text-bg-danger">Thất bại</span>
              <?php endif; ?>
            </td>
            <td><?= e((string) $row['message']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/_pagination.php'; ?>
