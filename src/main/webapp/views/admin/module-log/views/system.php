<?php
/** Nhật ký hoạt động hệ thống. Biến: $items, $filters, $pager, $users, $actions, $baseUrl. */
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
  <div class="col-12 col-md-3 col-xl-3">
    <label class="form-label" for="user_id">Người thực hiện</label>
    <select class="form-select" id="user_id" name="user_id">
      <option value="">Tất cả</option>
      <?php foreach ($users as $u): ?>
        <option value="<?= (int) $u['id'] ?>"<?= $filters['user_id'] === (int) $u['id'] ? ' selected' : '' ?>><?= e($u['full_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-3 col-xl-3">
    <label class="form-label" for="action">Thao tác</label>
    <select class="form-select" id="action" name="action">
      <option value="">Tất cả</option>
      <?php foreach ($actions as $a): ?>
        <option value="<?= e($a) ?>"<?= $filters['action'] === $a ? ' selected' : '' ?>><?= e(LogService::actionLabel((string) $a)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-xl-2 d-flex gap-2">
    <button type="submit" class="btn btn-primary">Lọc</button>
    <a class="btn btn-outline-secondary" href="<?= e($baseUrl) ?>">Đặt lại</a>
  </div>
</form>

<p class="text-secondary mb-2"><?= number_format((int) $pager['total'], 0, ',', '.') ?> bản ghi</p>

<div class="card">
  <div class="table-scroll">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr><th>Thời gian</th><th>Người thực hiện</th><th>Thao tác</th><th>Chi tiết</th><th>Địa chỉ IP</th><th>Kết quả</th></tr>
      </thead>
      <tbody>
        <?php if ($items === []): ?>
          <tr><td colspan="6" class="text-center text-secondary py-4">Không có bản ghi nào khớp bộ lọc.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $row): ?>
          <tr>
            <td class="text-nowrap"><?= e(date('d/m/Y H:i:s', strtotime((string) $row['created_at']))) ?></td>
            <td><?= $row['full_name'] !== null ? e($row['full_name']) : '<span class="text-secondary">Chưa đăng nhập</span>' ?></td>
            <td><?= e(LogService::actionLabel((string) $row['action'])) ?></td>
            <td><?= e((string) $row['detail']) ?></td>
            <td class="text-nowrap"><?= e((string) $row['ip_address']) ?></td>
            <td>
              <?php if ($row['status'] === 'success'): ?>
                <span class="badge text-bg-success">Thành công</span>
              <?php elseif ($row['status'] === 'failed'): ?>
                <span class="badge text-bg-danger">Thất bại</span>
              <?php else: ?>
                <span class="badge text-bg-secondary"><?= e($row['status']) ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/_pagination.php'; ?>
