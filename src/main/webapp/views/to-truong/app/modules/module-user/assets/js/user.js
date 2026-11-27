/**
 * JS của module-user: hộp thoại tạo/sửa tài khoản, khóa/mở khóa (gọi API JSON rồi tải lại trang).
 * Mục "Lớp dạy" chỉ hiện khi chọn vai trò Tổ trưởng hoặc Giáo viên.
 */
document.addEventListener('DOMContentLoaded', function () {
  const page = document.querySelector('[data-user]');
  if (!page) return;

  const api = page.dataset.api;
  const teachingRoles = JSON.parse(page.dataset.teachingRoles || '[]');
  const pageAlert = document.getElementById('page-alert');

  function showPageAlert(type, message) {
    pageAlert.className = 'alert alert-' + type;
    pageAlert.textContent = message;
    pageAlert.scrollIntoView({ block: 'nearest' });
  }

  const stored = sessionStorage.getItem('userMessage');
  if (stored) {
    sessionStorage.removeItem('userMessage');
    showPageAlert('success', stored);
  }

  function reloadWith(message) {
    sessionStorage.setItem('userMessage', message);
    window.location.reload();
  }

  function rowItem(button) {
    return JSON.parse(button.closest('tr').dataset.item);
  }

  const modalEl = document.getElementById('user-modal');
  if (!modalEl) return;               // chỉ xem (không có quyền ghi)

  const modal = new bootstrap.Modal(modalEl);
  const form = modalEl.querySelector('form');
  const saveButton = form.querySelector('[type="submit"]');
  const formAlert = form.querySelector('[data-form-alert]');
  const classGroup = form.querySelector('[data-class-group]');
  const classBoxes = form.querySelectorAll('[data-class-box]');
  const passwordGroup = form.querySelector('[data-password-group]');
  const usernameHint = form.querySelector('[data-username-hint]');
  const roleSelect = form.elements.role_id;
  let editingId = null;

  function clearErrors() {
    formAlert.className = 'alert alert-danger d-none';
    form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
    form.querySelectorAll('[data-error-for]').forEach(function (el) { el.textContent = ''; });
  }

  function currentRoleCode() {
    const option = roleSelect.options[roleSelect.selectedIndex];
    return option ? option.dataset.code || '' : '';
  }

  function syncClassGroup() {
    classGroup.classList.toggle('d-none', teachingRoles.indexOf(currentRoleCode()) === -1);
  }
  roleSelect.addEventListener('change', syncClassGroup);

  function openForm(item) {
    editingId = item ? item.id : null;
    modalEl.querySelector('.modal-title').textContent = item ? 'Sửa tài khoản' : 'Thêm tài khoản';
    clearErrors();

    form.elements.username.value = item ? item.username : '';
    form.elements.username.disabled = !!item;           // không đổi tên đăng nhập sau khi tạo
    usernameHint.classList.toggle('d-none', !!item);
    passwordGroup.classList.toggle('d-none', !!item);   // mật khẩu chỉ nhập khi tạo mới
    form.elements.password.value = '';
    form.elements.full_name.value = item ? item.full_name : '';
    form.elements.email.value = item ? item.email : '';
    form.elements.phone.value = item && item.phone ? item.phone : '';
    roleSelect.value = item ? item.role_id : '';

    const chosen = item ? item.class_ids : [];
    classBoxes.forEach(function (box) { box.checked = chosen.indexOf(parseInt(box.value, 10)) !== -1; });
    syncClassGroup();
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
      const body = {
        full_name: form.elements.full_name.value,
        email: form.elements.email.value,
        phone: form.elements.phone.value,
        role_id: roleSelect.value,
        class_ids: Array.prototype.filter.call(classBoxes, function (box) { return box.checked; })
          .map(function (box) { return parseInt(box.value, 10); }),
      };
      if (!editingId) {
        body.username = form.elements.username.value;
        body.password = form.elements.password.value;
      }
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

  document.querySelectorAll('[data-toggle-status]').forEach(function (button) {
    button.addEventListener('click', async function () {
      const item = rowItem(button);
      const locking = button.dataset.next === 'locked';
      if (locking && !window.confirm('Khóa tài khoản ' + item.username + '? Người này sẽ không đăng nhập được cho đến khi mở khóa.')) return;
      try {
        const result = await window.api(api + '/' + item.id + '/status', { method: 'PATCH', body: { status: button.dataset.next } });
        reloadWith(result.message);
      } catch (error) {
        showPageAlert('danger', error.message);
      }
    });
  });
});
