/**
 * JS của module-category: mở form thêm/sửa trong hộp thoại, đổi trạng thái, xóa; tất cả gọi API JSON
 * rồi tải lại trang để thấy danh sách mới. Thông báo thành công được giữ qua lần tải lại bằng sessionStorage.
 */
document.addEventListener('DOMContentLoaded', function () {
  const page = document.querySelector('[data-category]');
  if (!page) return;

  const api = page.dataset.api;
  const singular = page.dataset.singular;
  const defaults = JSON.parse(page.dataset.defaults || '{}');
  const pageAlert = document.getElementById('page-alert');

  function showPageAlert(type, message) {
    pageAlert.className = 'alert alert-' + type;
    pageAlert.textContent = message;
    pageAlert.scrollIntoView({ block: 'nearest' });
  }

  // Lọc tự gửi khi đổi lựa chọn
  document.querySelectorAll('select[data-autosubmit]').forEach(function (select) {
    select.addEventListener('change', function () { select.form.submit(); });
  });

  // Hiện thông báo của thao tác trước đó (trước khi tải lại trang)
  const stored = sessionStorage.getItem('categoryMessage');
  if (stored) {
    sessionStorage.removeItem('categoryMessage');
    showPageAlert('success', stored);
  }

  function reloadWith(message) {
    sessionStorage.setItem('categoryMessage', message);
    window.location.reload();
  }

  function rowItem(button) {
    return JSON.parse(button.closest('tr').dataset.item);
  }

  // ----- Hộp thoại thêm / sửa -----
  const modalEl = document.getElementById('category-modal');
  if (!modalEl) return;               // chỉ xem (không có quyền ghi)

  const modal = new bootstrap.Modal(modalEl);
  const form = modalEl.querySelector('form');
  const formAlert = form.querySelector('[data-form-alert]');
  const saveButton = form.querySelector('[type="submit"]');
  let editingId = null;

  function clearErrors() {
    formAlert.className = 'alert alert-danger d-none';
    form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
    form.querySelectorAll('[data-error-for]').forEach(function (el) { el.textContent = ''; });
  }

  function openForm(item) {
    editingId = item ? item.id : null;
    modalEl.querySelector('.modal-title').textContent = (item ? 'Sửa ' : 'Thêm ') + singular;
    clearErrors();
    Array.prototype.forEach.call(form.elements, function (field) {
      if (!field.name) return;
      const source = item ? item : defaults;
      field.value = source[field.name] === null || source[field.name] === undefined ? '' : source[field.name];
    });
    modal.show();
  }

  const addButton = document.querySelector('[data-add]');
  if (addButton) addButton.addEventListener('click', function () { openForm(null); });

  document.querySelectorAll('[data-edit]').forEach(function (button) {
    button.addEventListener('click', function () { openForm(rowItem(button)); });
  });

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    clearErrors();
    saveButton.disabled = true;

    try {
      const body = Object.fromEntries(new FormData(form).entries());
      const result = await window.api(editingId ? api + '/' + editingId : api, {
        method: editingId ? 'PUT' : 'POST',
        body: body,
      });
      reloadWith(result.message);
    } catch (error) {
      formAlert.textContent = error.message;
      formAlert.className = 'alert alert-danger';
      Object.keys(error.errors || {}).forEach(function (field) {
        const input = form.querySelector('[name="' + field + '"]');
        const slot = form.querySelector('[data-error-for="' + field + '"]');
        if (input) input.classList.add('is-invalid');
        if (slot) slot.textContent = error.errors[field];
      });
    } finally {
      saveButton.disabled = false;
    }
  });

  // ----- Đổi trạng thái -----
  document.querySelectorAll('[data-toggle-status]').forEach(function (button) {
    button.addEventListener('click', async function () {
      const item = rowItem(button);
      try {
        const result = await window.api(api + '/' + item.id + '/status', {
          method: 'PATCH',
          body: { status: button.dataset.next },
        });
        reloadWith(result.message);
      } catch (error) {
        showPageAlert('danger', error.message);
      }
    });
  });

  // ----- Xóa -----
  document.querySelectorAll('[data-delete]').forEach(function (button) {
    button.addEventListener('click', async function () {
      const item = rowItem(button);
      const label = item.name || item.code || '';
      if (!window.confirm('Xóa ' + singular + ' «' + label + '»? Thao tác này không hoàn tác được.')) return;
      try {
        const result = await window.api(api + '/' + item.id, { method: 'DELETE' });
        reloadWith(result.message);
      } catch (error) {
        showPageAlert('danger', error.message);
      }
    });
  });
});
