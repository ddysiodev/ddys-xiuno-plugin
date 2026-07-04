(function () {
  function ready(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback);
    } else {
      callback();
    }
  }

  ready(function () {
    var forms = document.querySelectorAll('[data-ddys-xiuno-request-form]');
    Array.prototype.forEach.call(forms, function (form) {
      form.addEventListener('submit', function (event) {
        if (!window.fetch || !window.FormData) return;
        event.preventDefault();
        var status = form.querySelector('.ddys-xiuno-status');
        if (status) status.textContent = '正在提交...';
        fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          credentials: 'same-origin'
        }).then(function (response) {
          return response.json();
        }).then(function (json) {
          if (json && json.success !== false) {
            form.reset();
            if (status) status.textContent = '求片已提交。';
          } else if (status) {
            status.textContent = json && json.message ? json.message : '提交失败。';
          }
        }).catch(function () {
          if (status) status.textContent = '网络异常，请稍后再试。';
        });
      });
    });
  });
})();
