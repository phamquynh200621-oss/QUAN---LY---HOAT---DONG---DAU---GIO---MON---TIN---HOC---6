<?php /** Màn hình chiếu cho cả lớp. Biến: $stateUrl. Nội dung do assets/js/session.js dựng. */ ?>
<div class="present" data-present data-state-url="<?= e($stateUrl) ?>">
  <header class="present-head">
    <div>
      <h1 class="present-title" data-title></h1>
      <p class="present-meta" data-meta></p>
    </div>
    <div class="present-timer-wrap">
      <div class="present-timer" data-timer>--:--</div>
      <div class="present-status" data-status></div>
    </div>
  </header>

  <section class="present-body-area" data-area>
    <p class="present-wait">Đang tải...</p>
  </section>
</div>
