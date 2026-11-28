<?php
/** Phiên hoạt động của tôi. Biến: $items, $pager, $baseUrl, $filters, $badges, $selectUrl, $canRecord. */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-secondary mb-0"><?= number_format((int) $pager['total'], 0, ',', '.') ?> phiên</p>
  <a class="btn btn-primary" href="<?= e($selectUrl) ?>">Chọn hoạt động mới</a>
</div>

<div class="card">
  <div class="table-scroll">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Hoạt động</th><th>Lớp</th><th>Hình thức</th><th>Trạng thái</th><th>Tạo lúc</th><th></th></tr></thead>
      <tbody>
        <?php if ($items === []): ?>
          <tr><td colspan="6" class="text-center text-secondary py-4">Bạn chưa tổ chức hoạt động nào.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $s): ?>
          <tr>
            <td><?= e($s['activity_title']) ?></td>
            <td><?= e((string) $s['class_code']) ?></td>
            <td><?= e($s['type_name']) ?></td>
            <td><span class="badge <?= e($badges[$s['status']] ?? 'text-bg-secondary') ?>"><?= e($s['status_label']) ?></span></td>
            <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime((string) $s['created_at']))) ?></td>
            <td class="text-end text-nowrap">
              <?php if ($s['status'] === 'selected'): ?>
                <a class="btn btn-primary btn-sm" href="<?= e(url('/sessions/' . $s['id'] . '/config')) ?>">Thiết lập</a>
              <?php else: ?>
                <a class="btn btn-primary btn-sm" href="<?= e(url('/sessions/' . $s['id'])) ?>">Điều khiển</a>
              <?php endif; ?>
              <?php if ($canRecord && $s['class_id'] !== null): ?>
                <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/sessions/' . $s['id'] . '/results')) ?>">Kết quả</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/_pagination.php'; ?>
