export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    if (form.dataset.module === "autocomplete") return;

    this.form = form;
    this.isLoading = false;
    this.timer = null;

    this.init();
  }

  init() {
    this.form.addEventListener("input", e => this.onChange(e));
    this.form.addEventListener("change", e => this.onChange(e));

    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });
  }

  onChange(e) {
    const el = e.target;

    if (!(el instanceof HTMLElement)) return;

    if (el.closest("[data-module='autocomplete']")) return;

    this.debounce();
  }

  debounce() {
    clearTimeout(this.timer);

    this.timer = setTimeout(() => {
      this.send();
    }, 200);
  }

  send() {
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl;
    const target = document.querySelector(this.form.dataset.target);

    if (!url || !target) return;

    this.isLoading = true;

    const params = new URLSearchParams(new FormData(this.form));

    fetch(url, {
      method: "POST",
      body: params
    })
      .then(res => res.text())
      .then(html => {
        target.innerHTML = html;

        window.dispatchEvent(new Event("ui:updated"));
      })
      .catch(err => {
        console.error("[FetchForm] erreur AJAX", err);
      })
      .finally(() => {
        this.isLoading = false;
      });
  }
}
