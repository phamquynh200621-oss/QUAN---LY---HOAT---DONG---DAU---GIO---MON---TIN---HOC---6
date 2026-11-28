/**
 * JS của module-session. Một tệp phục vụ các trang, nhận biết trang bằng thuộc tính data-*:
 *  - xem trước (#confirm-select, [data-toggle-answers]) · thiết lập (#config-form)
 *  - bảng điều khiển ([data-control]) · màn hình chiếu ([data-present])
 * Mọi nội dung người dùng đều được chèn bằng textContent (không dùng innerHTML) nên an toàn.
 */
document.addEventListener('DOMContentLoaded', function () {
  initPreview();
  initConfig();
  initControl();
  initPresent();
});

function el(tag, className, text) {
  const node = document.createElement(tag);
  if (className) node.className = className;
  if (text !== undefined && text !== null) node.textContent = text;
  return node;
}

function formatTime(seconds) {
  const s = Math.max(0, Math.floor(seconds));
  const m = Math.floor(s / 60);
  return String(m).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
}

// ---------------------------------------------------------------- Xem trước
function initPreview() {
  const toggle = document.querySelector('[data-toggle-answers]');
  if (toggle) toggle.addEventListener('change', function () {
    document.querySelectorAll('[data-answer-mark]').forEach(function (node) { node.classList.toggle('d-none', !toggle.checked); });
  });

  const button = document.getElementById('confirm-select');
  if (!button) return;
  button.addEventListener('click', async function () {
    button.disabled = true;
    try {
      const result = await window.api(button.dataset.api, { method: 'POST', body: { activity_id: button.dataset.activity } });
      window.location.href = button.dataset.base + '/' + result.data.id + '/config';
    } catch (error) {
      const box = document.getElementById('page-alert');
      box.className = 'alert alert-danger';
      box.textContent = error.message;
      button.disabled = false;
    }
  });
}

// ---------------------------------------------------------------- Thiết lập
function initConfig() {
  const form = document.getElementById('config-form');
  if (!form) return;
  const alertBox = form.querySelector('[data-form-alert]');
  const submit = form.querySelector('[type="submit"]');

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    alertBox.className = 'alert alert-danger d-none';
    form.querySelectorAll('.is-invalid').forEach(function (n) { n.classList.remove('is-invalid'); });
    form.querySelectorAll('[data-error-for]').forEach(function (n) { n.textContent = ''; });
    if (submit) submit.disabled = true;

    try {
      await window.api(form.dataset.api, { method: 'PUT', body: Object.fromEntries(new FormData(form).entries()) });
      window.location.href = form.dataset.next;
    } catch (error) {
      alertBox.textContent = error.message;
      alertBox.className = 'alert alert-danger';
      Object.keys(error.errors || {}).forEach(function (field) {
        const input = form.elements[field];
        const slot = form.querySelector('[data-error-for="' + field + '"]');
        if (input) input.classList.add('is-invalid');
        if (slot) slot.textContent = error.errors[field];
      });
      if (submit) submit.disabled = false;
    }
  });
}

// ---------------------------------------------------------------- Đồng hồ dùng chung
// Nhận trạng thái từ máy chủ rồi tự đếm từng giây ở trình duyệt giữa hai lần hỏi lại.
function makeClock(timerEl, onTick) {
  let base = null;
  setInterval(function () {
    if (base) onTick(currentRemaining(base));
  }, 1000);

  function currentRemaining(b) {
    if (b.duration === 0) return null;
    const extra = b.running ? (Date.now() - b.fetchedAt) / 1000 : 0;
    return b.remaining - extra;
  }

  return {
    update: function (state) {
      base = {
        duration: state.duration_seconds,
        remaining: state.remaining_seconds === null ? 0 : state.remaining_seconds,
        running: state.status === 'running',
        fetchedAt: Date.now(),
      };
      onTick(currentRemaining(base));
    },
  };
}

function paintTimer(timerEl, remaining, state, noteEl) {
  if (state.duration_seconds === 0) { timerEl.textContent = formatTime(state.elapsed_seconds); return; }
  timerEl.textContent = formatTime(remaining === null ? 0 : remaining);
  timerEl.classList.toggle('is-low', remaining !== null && remaining > 0 && remaining <= 30 && state.status === 'running');
  timerEl.classList.toggle('is-over', remaining !== null && remaining <= 0 && state.status !== 'configured');
  if (noteEl) noteEl.textContent = (remaining !== null && remaining <= 0 && state.status !== 'configured') ? 'Đã hết giờ' : 'Thời gian còn lại';
}

// ---------------------------------------------------------------- Bảng điều khiển
function initControl() {
  const root = document.querySelector('[data-control]');
  if (!root) return;

  const badge = root.querySelector('[data-status-badge]');
  const timerEl = root.querySelector('[data-timer]');
  const noteEl = root.querySelector('[data-timer-note]');
  const questionArea = root.querySelector('[data-question-area]');
  const position = root.querySelector('[data-question-position]');
  const revealButton = root.querySelector('[data-reveal-button]');
  const alertBox = document.getElementById('page-alert');
  const buttons = root.querySelectorAll('[data-command]');
  const badgeClass = { selected: 'text-bg-secondary', configured: 'text-bg-info', running: 'text-bg-success', paused: 'text-bg-warning', finished: 'text-bg-dark' };
  let lastState = null;

  const clock = makeClock(timerEl, function (remaining) { if (lastState) paintTimer(timerEl, remaining, lastState, noteEl); });

  function enabledFor(status) {
    return {
      start: status === 'configured', pause: status === 'running', resume: status === 'paused',
      end: status === 'running' || status === 'paused',
      prev: status !== 'finished', next: status !== 'finished', reveal: status !== 'finished',
    };
  }

  function render(state) {
    lastState = state;
    badge.textContent = state.status_label;
    badge.className = 'badge ' + (badgeClass[state.status] || 'text-bg-secondary');

    const allowed = enabledFor(state.status);
    buttons.forEach(function (b) {
      b.disabled = !allowed[b.dataset.command] || (['prev', 'next', 'reveal'].indexOf(b.dataset.command) !== -1 && state.question_total === 0);
    });
    revealButton.textContent = state.reveal ? 'Ẩn đáp án' : 'Hiện đáp án';
    clock.update(state);

    questionArea.innerHTML = '';
    if (state.question_total === 0) {
      position.textContent = '';
      questionArea.appendChild(el('p', 'text-secondary mb-0', 'Hoạt động này không có câu hỏi.'));
      return;
    }
    position.textContent = '(' + (state.current + 1) + '/' + state.question_total + ')';
    const q = state.questions[state.current];
    questionArea.appendChild(el('p', 'fw-medium', q.text));
    const list = el('ul', 'list-unstyled mb-2');
    q.answers.forEach(function (a, i) {
      const li = el('li', 'mb-1', String.fromCharCode(65 + i) + '. ' + a.text);
      if (a.is_correct) li.appendChild(el('span', 'badge text-bg-success ms-2', 'Đúng'));
      list.appendChild(li);
    });
    questionArea.appendChild(list);
    if (q.explanation) questionArea.appendChild(el('p', 'small text-secondary mb-0', 'Giải thích: ' + q.explanation));
  }

  async function refresh() {
    try {
      render((await window.api(root.dataset.stateUrl)).data);
    } catch (error) {
      alertBox.className = 'alert alert-danger';
      alertBox.textContent = error.message;
    }
  }

  buttons.forEach(function (button) {
    button.addEventListener('click', async function () {
      const command = button.dataset.command;
      if (command === 'end' && !window.confirm('Kết thúc phiên hoạt động này?')) return;
      alertBox.className = 'alert d-none';
      try {
        render((await window.api(root.dataset.controlApi, { method: 'POST', body: { command: command } })).data);
      } catch (error) {
        alertBox.className = 'alert alert-danger';
        alertBox.textContent = error.message;
      }
    });
  });

  refresh();
  setInterval(refresh, 2000);
}

// ---------------------------------------------------------------- Màn hình chiếu
function initPresent() {
  const root = document.querySelector('[data-present]');
  if (!root) return;

  const titleEl = root.querySelector('[data-title]');
  const metaEl = root.querySelector('[data-meta]');
  const timerEl = root.querySelector('[data-timer]');
  const statusEl = root.querySelector('[data-status]');
  const area = root.querySelector('[data-area]');
  let lastState = null;

  const clock = makeClock(timerEl, function (remaining) { if (lastState) paintTimer(timerEl, remaining, lastState, null); });

  function render(state) {
    lastState = state;
    titleEl.textContent = state.title;
    metaEl.textContent = [state.class_name ? 'Lớp ' + state.class_name : '', state.type_name, state.play_mode].filter(Boolean).join('  |  ');
    statusEl.textContent = state.status_label;
    clock.update(state);

    area.innerHTML = '';
    const started = state.status === 'running' || state.status === 'paused' || state.status === 'finished';

    if (state.status === 'finished') {
      area.appendChild(el('p', 'present-wait', 'Hoạt động đã kết thúc. Cảm ơn cả lớp!'));
      return;
    }
    if (!started || state.question_total === 0) {
      const text = state.content || state.description || (started ? '' : 'Chuẩn bị bắt đầu...');
      area.appendChild(el('p', 'present-content', text));
      return;
    }

    const q = state.question;
    area.appendChild(el('div', 'present-position', 'Câu ' + (state.current + 1) + ' / ' + state.question_total));
    area.appendChild(el('div', 'present-question', q.text));
    if (q.answers.length) {
      const list = el('ul', 'present-answers');
      q.answers.forEach(function (a, i) {
        const item = el('li', 'present-answer' + (state.reveal && a.is_correct ? ' is-correct' : ''));
        item.appendChild(el('span', 'present-letter', String.fromCharCode(65 + i) + '.'));
        item.appendChild(el('span', '', a.text));
        list.appendChild(item);
      });
      area.appendChild(list);
    }
    if (state.reveal && q.explanation) area.appendChild(el('div', 'present-explain', 'Giải thích: ' + q.explanation));
  }

  async function refresh() {
    try {
      render((await window.api(root.dataset.stateUrl)).data);
    } catch (error) {
      area.innerHTML = '';
      area.appendChild(el('p', 'present-wait', 'Mất kết nối với máy chủ. Đang thử lại...'));
    }
  }
  refresh();
  setInterval(refresh, 2000);
}
