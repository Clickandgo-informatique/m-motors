export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    if (form.dataset.module === "autocomplete") return;

    this.form = form;
    this.isLoading = false;
    this.timer = null;

    // 🔥 NO AUTO TRIGGER
    this.ready = false;

    this.init();
  }

  init() {
    this.form.addEventListener("input", e => this.onChange(e));
    this.form.addEventListener("change", e => this.onChange(e));

    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    //ARMEMENT APRES STABILISATION DOM
    window.addEventListener("DOMContentLoaded", () => {
      setTimeout(() => {
        this.ready = true;
      }, 300);
    });
  }

  onChange(e) {
    if (!this.ready) return; // BLOQUAGE CRITIQUE

    const el = e.target;
    if (!(el instanceof HTMLElement)) return;

    if (el.closest("[data-module='autocomplete']")) return;

    this.debounce();
  }

  debounce() {
    clearTimeout(this.timer);

    this.timer = setTimeout(() => {
      this.send();
    }, 250);
  }

  send() {
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl;
    const target = document.querySelector(this.form.dataset.target);
    const filtersTarget = document.querySelector(
      this.form.dataset.filtersTarget
    );
    const paginationTarget = document.querySelector(
      this.form.dataset.paginationTarget
    );

    if (!url || !target) return;

    this.isLoading = true;

    const params = new URLSearchParams(new FormData(this.form));

    fetch(url, {
      method: "POST",
      body: params
    })
      .then(res => res.json())
      .then(data => {
        if (data.list && target) {
          target.innerHTML = data.list;
        }

        if (data.filters && filtersTarget) {
          filtersTarget.innerHTML = data.filters;
        }

        if (data.pagination && paginationTarget) {
          paginationTarget.innerHTML = data.pagination;
        }

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
