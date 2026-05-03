/**
 * FetchForm
 * ------------------------------------------------------------------
 * Formulaire AJAX générique
 * ------------------------------------------------------------------
 */

export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.isLoading = false;

    this.init();
  }

  init() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    this.form.addEventListener("change", e => {
      const el = e.target;

      if (!(el instanceof HTMLElement)) return;
      if (!el.matches("input, select, textarea")) return;

      if (el.closest("[data-module='autocomplete']")) return;

      this.send();
    });
  }

  merge(formData) {
    document.querySelectorAll("[data-module='fetch-form']").forEach(other => {
      if (other === this.form) return;

      new FormData(other).forEach((v, k) => {
        formData.set(k, v);
      });
    });
  }

  send() {
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl || this.form.action;

    const target = document.querySelector(this.form.dataset.target);

    if (!url || !target) return;

    this.isLoading = true;

    const formData = new FormData(this.form);
    this.merge(formData);

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
      .finally(() => {
        this.isLoading = false;
      });
  }
}
