/**
 * FetchForm.js
 * Système AJAX générique stable
 */

export default class FetchForm {
  constructor(form) {
    this.form = form;
    this.isLoading = false;

    this.init();
  }

  init() {
    this.bindSubmit();
    this.bindChangeDelegation();
  }

  bindSubmit() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });
  }

  bindChangeDelegation() {
    this.form.addEventListener("change", e => {
      const el = e.target;

      if (el.matches("input, select, textarea")) {
        this.send();
      }
    });
  }

  send() {
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl || this.form.action;
    const targetSelector = this.form.dataset.target;

    if (!url || !targetSelector) {
      console.error("[FetchForm] missing config");
      return;
    }

    const target = document.querySelector(targetSelector);
    if (!target) return;

    this.isLoading = true;

    const formData = new FormData(this.form);

    fetch(url, {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      },
      body: formData
    })
      .then(r => r.text())
      .then(html => {
        target.innerHTML = html;
        window.dispatchEvent(new Event("ui:updated"));
      })
      .catch(err => console.error(err))
      .finally(() => {
        this.isLoading = false;
      });
  }
}
