<?php
/** Phân quyền: vai trò và ma trận quyền (chỉ xem). Biến: $roles, $functions, $roleCounts, $usersUrl. */
$countByRole = [];
foreach ($roleCounts as $rc) {
    $countByRole[(int) $rc['id']] = (int) $rc['user_count'];
}
$levelClass = ['Q' => 'text-bg-dark', 'D' => 'text-bg-primary', 'T' => 'text-bg-success', 'C' => 'text-bg-info', 'K' => 'text-bg-light border'];
?>
<div class="row g-3 mb-4">
  <?php foreach ($roles as $role): ?>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <h2 class="h6 mb-1"><?= e($role['name']) ?></h2>
          <p class="mb-2 text-secondary"><?= $countByRole[(int) $role['id']] ?? 0 ?> tài khoản</p>
          <a href="<?= e($usersUrl . '?role_id=' . (int) $role['id']) ?>">Xem danh sách</a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<p class="text-secondary">Vai trò của mỗi tài khoản được chọn khi tạo hoặc sửa tài khoản. Bảng dưới là quyền của từng vai trò (chỉ xem).</p>

<div class="card">
  <div class="table-scroll">
    <table class="table table-sm align-middle mb-0">
      <thead>
        <tr>
          <th>TT</th><th>Chức năng</th>
          <?php foreach ($roles as $role): ?><th><?= e($role['name']) ?></th><?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php $lastGroup = null; ?>
        <?php foreach ($functions as $fn): ?>
          <?php if ($fn['group_name'] !== $lastGroup): $lastGroup = $fn['group_name']; ?>
            <tr class="table-light"><td colspan="<?= count($roles) + 2 ?>" class="fw-semibold"><?= e($lastGroup) ?></td></tr>
          <?php endif; ?>
          <tr>
            <td><?= (int) $fn['code'] ?></td>
            <td><?= e($fn['name']) ?></td>
            <?php foreach ($roles as $role): ?>
              <?php
                $cell  = $fn['cells'][(int) $role['id']] ?? ['level' => 'K', 'scope' => 'all'];
                $scope = UserService::SCOPES[$cell['scope']] ?? '';
              ?>
              <td class="text-nowrap">
                <span class="badge <?= e($levelClass[$cell['level']] ?? 'text-bg-light') ?>"><?= e(UserService::LEVELS[$cell['level']] ?? $cell['level']) ?></span>
                <?php if ($cell['level'] !== 'K' && $scope !== ''): ?><span class="small text-secondary">(<?= e($scope) ?>)</span><?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
