/**
 * JS của module-student: hộp thoại thêm/sửa, đổi trạng thái, xóa, nhập CSV (gọi API JSON rồi tải lại trang).
 * Thông báo thành công được giữ qua lần tải lại bằng sessionStorage.
 */
document.addEventListener('DOMContentLoaded', function () {
  const page = document.querySelector('[data-student]');
  if (!page) return;

  const api = page.dataset.api;
  const defaultClass = page.dataset.defaultClass || '';
  const pageAlert = document.getElementById('page-alert');

  function showPageAlert(type, message) {
    pageAlert.className = 'alert alert-' + type;
    pageAlert.textContent = message;
    pageAlert.scrollIntoView({ block: 'nearest' });
  }

  const stored = sessionStorage.getItem('studentMessage');
  if (stored) {
    sessionStorage.removeItem('studentMessage');
    showPageAlert('success', stored);
  }

  function reloadWith(message) {
    sessionStorage.setItem('studentMessage', message);
    window.location.reload();
  }

  function rowItem(button) {
    return JSON.parse(button.closest('tr').dataset.item);
  }

  const modalEl = document.getElementById('student-modal');
  if (!modalEl) return;               // chỉ xem (không có quyền ghi)

  function clearErrors(form) {
    const box = form.querySelector('[data-form-alert]');
    if (box) box.className = 'alert d-none';
    form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
    form.querySelectorAll('[data-error-for]').forEach(function (el) { el.textContent = ''; });
  }

  function showErrors(form, error) {
    const box = form.querySelector('[data-form-alert]');
    if (box) { box.className = 'alert alert-danger'; box.textContent = error.message; }
    Object.keys(error.errors || {}).forEach(function (field) {
      const input = form.querySelector('[name="' + field + '"]');
      const slot = form.querySelector('[data-error-for="' + field + '"]');
      if (input) input.classList.add('is-invalid');
      if (slot) slot.textContent = error.errors[field];
    });
  }

  // ----- Thêm / sửa -----
  const modal = new bootstrap.Modal(modalEl);
  const form = modalEl.querySelector('form');
  const saveButton = form.querySelector('[type="submit"]');
  let editingId = null;

  function openForm(item) {
    editingId = item ? item.id : null;
    modalEl.querySelector('.modal-title').textContent = item ? 'Sửa học sinh' : 'Thêm học sinh';
    clearErrors(form);
    form.elements.student_code.value = item ? item.student_code : '';
    form.elements.full_name.value = item ? item.full_name : '';
    form.elements.dob.value = item && item.dob ? item.dob : '';
    form.elements.gender.value = item && item.gender ? item.gender : '';
    form.elements.class_id.value = item ? item.class_id : defaultClass;
    modal.show();
  }

  const addButton = document.querySelector('[data-add]');
  if (addButton) addButton.addEventListener('click', function () { openForm(null); });
  document.querySelectorAll('[data-edit]').forEach(function (button) {
    button.addEventListener('click', function () { openForm(rowItem(button)); });
  });

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    clearErrors(form);
    saveButton.disabled = true;
    try {
      const body = Object.fromEntries(new FormData(form).entries());
      const result = await window.api(editingId ? api + '/' + editingId : api, {
        method: editingId ? 'PUT' : 'POST',
        body: body,
      });
      reloadWith(result.message);
    } catch (error) {
      showErrors(form, error);
    } finally {
      saveButton.disabled = false;
    }
  });

  // ----- Đổi trạng thái, xóa -----
  document.querySelectorAll('[data-toggle-status]').forEach(function (button) {
    button.addEventListener('click', async function () {
      const item = rowItem(button);
      try {
        const result = await window.api(api + '/' + item.id + '/status', { method: 'PATCH', body: { status: button.dataset.next } });
        reloadWith(result.message);
      } catch (error) {
        showPageAlert('danger', error.message);
      }
    });
  });

  document.querySelectorAll('[data-delete]').forEach(function (button) {
    button.addEventListener('click', async function () {
      const item = rowItem(button);
      if (!window.confirm('Xóa học sinh ' + item.student_code + ' - ' + item.full_name + '? Thao tác này không hoàn tác được.')) return;
      try {
        const result = await window.api(api + '/' + item.id, { method: 'DELETE' });
        reloadWith(result.message);
      } catch (error) {
        showPageAlert('danger', error.message);
      }
    });
  });

  // ----- Nhập CSV -----
  const importEl = document.getElementById('import-modal');
  const importModal = new bootstrap.Modal(importEl);
  const importForm = importEl.querySelector('form');
  const importButton = importForm.querySelector('[type="submit"]');
  const errorList = importEl.querySelector('[data-import-errors]');

  const openImport = document.querySelector('[data-import]');
  if (openImport) openImport.addEventListener('click', function () {
    clearErrors(importForm);
    errorList.className = 'small text-danger ps-3 mb-0 d-none';
    errorList.innerHTML = '';
    importForm.reset();
    importForm.elements.class_id.value = defaultClass;
    importModal.show();
  });

  importForm.addEventListener('submit', async function (event) {
    event.preventDefault();
    clearErrors(importForm);
    errorList.className = 'small text-danger ps-3 mb-0 d-none';
    errorList.innerHTML = '';
    importButton.disabled = true;

    try {
      const result = await window.api(api + '/import', { method: 'POST', body: new FormData(importForm) });
      const data = result.data || {};

      if (data.failed > 0) {
        const box = importForm.querySelector('[data-form-alert]');
        box.className = 'alert alert-warning';
        box.textContent = result.message;
        (data.errors || []).forEach(function (item) {
          const li = document.createElement('li');
          li.textContent = 'Dòng ' + item.line + ': ' + item.message;
          errorList.appendChild(li);
        });
        errorList.className = 'small text-danger ps-3 mb-0';
        if (data.imported > 0) sessionStorage.setItem('studentMessage', result.message);
        importEl.addEventListener('hidden.bs.modal', function () { if (data.imported > 0) window.location.reload(); }, { once: true });
      } else {
        reloadWith(result.message);
      }
    } catch (error) {
      showErrors(importForm, error);
    } finally {
      importButton.disabled = false;
    }
  });
});
