/**
 * JS của module-result:
 *  - Bảng nhập kết quả ([data-sheet]): «Lưu nháp» gửi PUT, «Lưu chính thức» gửi PUT rồi POST .../save.
 *    Dòng sai được tô đỏ kèm lý do; khi có dòng sai thì không lưu dòng nào.
 *  - Trang lịch sử ([data-history]): xem chi tiết từng lần đánh giá trong hộp thoại.
 * Nội dung người dùng chỉ được chèn bằng textContent.
 */
function el(tag, className, text) {
  const node = document.createElement(tag);
  if (className) node.className = className;
  if (text !== undefined && text !== null) node.textContent = text;
  return node;
}

document.addEventListener('DOMContentLoaded', function () {
  initSheet();
  initHistory();
});

function initSheet() {
  const form = document.querySelector('[data-sheet]');
  if (!form) return;

  const api = form.dataset.api;
  const alertBox = document.getElementById('page-alert');
  const buttons = form.querySelectorAll('[data-action]');
  const stored = sessionStorage.getItem('resultMessage');
  if (stored) {
    sessionStorage.removeItem('resultMessage');
    alertBox.className = 'alert alert-success';
    alertBox.textContent = stored;
  }

  function collect() {
    return Array.prototype.map.call(form.querySelectorAll('tr[data-student]'), function (row) {
      return {
        student_id: row.dataset.student,
        score: row.querySelector('[name="score"]').value,
        rating_level: row.querySelector('[name="rating_level"]').value,
        comment: row.querySelector('[name="comment"]').value,
      };
    });
  }

  function clearRowErrors() {
    form.querySelectorAll('tr.table-danger').forEach(function (row) { row.classList.remove('table-danger'); });
    form.querySelectorAll('[data-row-error]').forEach(function (n) { n.textContent = ''; n.classList.remove('d-block'); });
  }

  function showErrors(error) {
    alertBox.className = 'alert alert-danger';
    alertBox.textContent = error.message;
    Object.keys(error.errors || {}).forEach(function (key) {
      const row = form.querySelector('tr[data-student="' + key.replace('student_', '') + '"]');
      if (!row) return;
      row.classList.add('table-danger');
      const slot = row.querySelector('[data-row-error]');
      slot.textContent = error.errors[key];
      slot.classList.add('d-block');
    });
    const first = form.querySelector('tr.table-danger');
    if (first) first.scrollIntoView({ block: 'center' });
  }

  buttons.forEach(function (button) {
    button.addEventListener('click', async function () {
      const finalize = button.dataset.action === 'final';
      if (finalize && !window.confirm('Lưu chính thức kết quả của phiên này?')) return;

      clearRowErrors();
      alertBox.className = 'alert d-none';
      buttons.forEach(function (b) { b.disabled = true; });

      try {
        let result = await window.api(api, { method: 'PUT', body: { items: collect() } });
        if (finalize) result = await window.api(api + '/save', { method: 'POST' });
        sessionStorage.setItem('resultMessage', result.message);
        window.location.reload();
      } catch (error) {
        showErrors(error);
        buttons.forEach(function (b) { b.disabled = false; });
      }
    });
  });
}

function initHistory() {
  const page = document.querySelector('[data-history]');
  if (!page) return;

  const modalEl = document.getElementById('history-modal');
  const modal = new bootstrap.Modal(modalEl);
  const body = modalEl.querySelector('[data-history-body]');

  function show(node) { body.innerHTML = ''; body.appendChild(node); }
  function value(text, fallback) { return text === null || text === '' || text === undefined ? fallback : String(text); }

  page.querySelectorAll('[data-history-btn]').forEach(function (button) {
    button.addEventListener('click', async function () {
      show(el('p', 'text-secondary mb-0', 'Đang tải...'));
      modal.show();
      try {
        const data = (await window.api(page.dataset.api + '/' + button.dataset.id + '/history')).data;
        const wrap = el('div');
        wrap.appendChild(el('p', 'fw-medium mb-1', data.result.student));
        wrap.appendChild(el('p', 'text-secondary', data.result.activity));

        if (!data.entries.length) {
          wrap.appendChild(el('p', 'text-secondary mb-0', 'Chưa có lần đánh giá nào.'));
        } else {
          const tableWrap = el('div', 'table-scroll');
          const table = el('table', 'table table-sm align-middle mb-0');
          const head = el('tr');
          ['Thời gian', 'Điểm', 'Mức', 'Nhận xét', 'Người ghi'].forEach(function (t) { head.appendChild(el('th', '', t)); });
          const thead = el('thead'); thead.appendChild(head); table.appendChild(thead);

          const tbody = el('tbody');
          data.entries.forEach(function (entry) {
            const tr = el('tr');
            const time = new Date(entry.changed_at.replace(' ', 'T'));
            tr.appendChild(el('td', 'text-nowrap', isNaN(time) ? entry.changed_at : time.toLocaleString('vi-VN')));
            tr.appendChild(el('td', '', value(entry.score, '')));
            tr.appendChild(el('td', '', entry.rating_level));
            tr.appendChild(el('td', '', entry.comment));
            tr.appendChild(el('td', '', entry.changed_by));
            tbody.appendChild(tr);
          });
          table.appendChild(tbody);
          tableWrap.appendChild(table);
          wrap.appendChild(tableWrap);
        }
        show(wrap);
      } catch (error) {
        show(el('p', 'text-danger mb-0', error.message));
      }
    });
  });
}
