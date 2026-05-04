import EventBus from "./EventBus.js";

export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    /**
     * IMPORTANT :
     * exclusion stricte autocomplete
     */
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

    /**
     * sécurité autocomplete
     */
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

    fetch(url)
      .then(response => response.text())
      .then(html => {
        console.log(html);

        target.innerHTML = html;

        EventBus.emit("ui:updated", {
          target,
          form: this.form
        });
      })
      .catch(err => {
        console.error("[FetchForm] erreur AJAX", err);
      })
      .finally(() => {
        this.isLoading = false;
      });
  }
}
