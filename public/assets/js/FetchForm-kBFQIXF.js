export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    if (form.dataset.module === "autocomplete") return;

    this.form = form;
    this.isLoading = false;
    this.timer = null;

    // bloque les triggers init DOM
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

    // ARMEMENT après stabilisation complète UI
    setTimeout(() => {
      this.ready = true;
    }, 400);
  }

  onChange(e) {
    if (!this.ready) return;

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

    //reset pagination à chaque action filtre
    const pageInput = this.form.querySelector('input[name="page"]');
    if (pageInput) {
      pageInput.value = 1;
    }

    const params = new URLSearchParams(new FormData(this.form));

    fetch(url, {
      method: "POST",
      body: params
    })
      .then(res => res.json())
      .then(data => {
        // LISTING
        if (data.list !== undefined && target) {
          target.innerHTML = data.list;
        }

        // FILTERS (sidebar dynamique si utilisé)
        if (data.filters !== undefined && filtersTarget) {
          filtersTarget.innerHTML = data.filters;
        }

        // PAGINATION
        if (data.pagination !== undefined && paginationTarget) {
          paginationTarget.innerHTML = data.pagination;
        }

        //reset ready state des composants
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
