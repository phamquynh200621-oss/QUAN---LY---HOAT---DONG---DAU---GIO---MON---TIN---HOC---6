<?php
/** Phân trang dùng chung cho hai trang nhật ký. Biến: $pager, $baseUrl, $filters. */
if ($pager['pages'] <= 1) {
    return;
}
$makeLink = static function (int $page) use ($baseUrl, $filters): string {
    $query = array_filter($filters, static fn ($v) => $v !== null && $v !== '');
    $query['page'] = $page;
    return $baseUrl . '?' . http_build_query($query);
};
$first = max(1, $pager['page'] - 2);
$last  = min($pager['pages'], $pager['page'] + 2);
?>
<nav aria-label="Phân trang nhật ký" class="mt-3">
  <ul class="pagination pagination-sm flex-wrap mb-0">
    <li class="page-item<?= $pager['page'] <= 1 ? ' disabled' : '' ?>">
      <a class="page-link" href="<?= e($makeLink(max(1, $pager['page'] - 1))) ?>">Trước</a>
    </li>
    <?php for ($i = $first; $i <= $last; $i++): ?>
      <li class="page-item<?= $i === $pager['page'] ? ' active' : '' ?>">
        <a class="page-link" href="<?= e($makeLink($i)) ?>"<?= $i === $pager['page'] ? ' aria-current="page"' : '' ?>><?= $i ?></a>
      </li>
    <?php endfor; ?>
    <li class="page-item<?= $pager['page'] >= $pager['pages'] ? ' disabled' : '' ?>">
      <a class="page-link" href="<?= e($makeLink(min($pager['pages'], $pager['page'] + 1))) ?>">Sau</a>
    </li>
  </ul>
</nav>
