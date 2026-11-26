/**
 * JS của module-auth: gửi các form có thuộc tính data-api bằng AJAX (window.api trong main.js).
 *  - Lỗi theo trường hiện dưới ô nhập (.invalid-feedback[data-error-for]).
 *  - Thông báo chung hiện trong [data-form-alert].
 *  - Thành công: nếu phản hồi có data.redirect thì chuyển trang; nếu form có data-reset-on-success thì xóa nội dung form.
 */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form[data-api]').forEach(function (form) {
    const alertBox = document.querySelector('[data-form-alert]');
    const button = form.querySelector('[type="submit"]');

    function showAlert(type, message) {
      if (!alertBox) return;
      alertBox.className = 'alert alert-' + type;
      alertBox.textContent = message;
    }

    function clearErrors() {
      if (alertBox) alertBox.className = 'alert d-none';
      form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
      form.querySelectorAll('[data-error-for]').forEach(function (el) { el.textContent = ''; });
    }

    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      clearErrors();

      const original = button.textContent;
      button.disabled = true;
      button.textContent = 'Đang xử lý...';

      try {
        const body = Object.fromEntries(new FormData(form).entries());
        const result = await window.api(form.dataset.api, { method: 'POST', body: body });

        showAlert('success', result.message || 'Thành công.');
        if (result.data && result.data.redirect) {
          window.location.href = result.data.redirect;
          return;
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
        button.disabled = false;
        button.textContent = original;
      }
    });
  });
});
