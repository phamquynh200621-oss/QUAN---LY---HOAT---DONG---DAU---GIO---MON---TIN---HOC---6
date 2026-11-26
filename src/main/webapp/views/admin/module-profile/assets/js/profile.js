/**
 * JS của module-profile. Xử lý các form có thuộc tính data-api:
 *   data-method="PUT"        phương thức HTTP (mặc định POST)
 *   data-multipart           gửi FormData (tải tệp)
 *   data-auto-submit         tự gửi khi chọn tệp
 *   data-reset-on-success    xóa nội dung form sau khi thành công
 * Lỗi theo trường hiện ở .invalid-feedback[data-error-for]; thông báo chung ở [data-form-alert] trong form.
 */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form[data-api]').forEach(function (form) {
    const alertBox = form.querySelector('[data-form-alert]');
    const button = form.querySelector('[type="submit"]');

    function showAlert(type, message) {
      if (!alertBox) return;
      alertBox.className = 'alert alert-' + type + (form.hasAttribute('data-auto-submit') ? ' mt-2 mb-0 py-2' : '');
      alertBox.textContent = message;
    }

    function clearErrors() {
      if (alertBox) alertBox.className = 'alert d-none';
      form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
      form.querySelectorAll('[data-error-for]').forEach(function (el) { el.textContent = ''; });
    }

    function showAvatar(url) {
      const box = document.querySelector('[data-avatar]');
      if (!box) return;
      box.innerHTML = '';
      const img = document.createElement('img');
      img.src = url;
      img.alt = 'Ảnh đại diện';
      box.appendChild(img);
    }

    async function send() {
      clearErrors();
      const original = button ? button.textContent : '';
      if (button) { button.disabled = true; button.textContent = 'Đang lưu...'; }

      try {
        const multipart = form.hasAttribute('data-multipart');
        const body = multipart ? new FormData(form) : Object.fromEntries(new FormData(form).entries());
        const result = await window.api(form.dataset.api, { method: form.dataset.method || 'POST', body: body });

        showAlert('success', result.message || 'Đã lưu.');
        if (result.data && result.data.avatar_url) showAvatar(result.data.avatar_url);
        if (result.data && result.data.full_name) {
          const name = document.querySelector('.user-menu-name');
          if (name) name.textContent = result.data.full_name;
        }
        if (form.hasAttribute('data-reset-on-success')) form.reset();
      } catch (error) {
        showAlert('danger', error.message);
        Object.keys(error.errors || {}).forEach(function (field) {
          const input = form.querySelector('[name="' + field + '"]');
          const slot = form.querySelector('[data-error-for="' + field + '"]');
          if (input) input.classList.add('is-invalid');
          if (slot) slot.textContent = error.errors[field];
        });
      } finally {
        if (button) { button.disabled = false; button.textContent = original; }
        if (form.hasAttribute('data-auto-submit')) form.reset();
      }
    }

    form.addEventListener('submit', function (event) { event.preventDefault(); send(); });

    if (form.hasAttribute('data-auto-submit')) {
      const fileInput = form.querySelector('input[type="file"]');
      if (fileInput) fileInput.addEventListener('change', function () { if (fileInput.files.length) send(); });
    }
  });
});
