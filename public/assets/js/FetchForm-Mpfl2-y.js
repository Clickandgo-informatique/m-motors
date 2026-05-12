export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.isLoading = false;
    this.timer = null;

    // Empêche les triggers init DOM (sliders, radios, etc.)
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

    // Activation différée pour éviter auto-trigger au chargement
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
    console.log([...new FormData(this.form).entries()]);
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl;

    const target = document.querySelector(this.form.dataset.target);
    const paginationTop = document.querySelector(
      '[data-target="pagination-top"]'
    );
    const paginationBottom = document.querySelector(
      '[data-target="pagination-bottom"]'
    );

    if (!url || !target) return;

    this.isLoading = true;

    // Reset page à chaque filtre (évite pagination incohérente)
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
        // Liste véhicules
        if (data.list) {
          target.innerHTML = data.list;
        }

        // Pagination top
        if (data.pagination_top && paginationTop) {
          paginationTop.innerHTML = data.pagination_top;
        }

        // Pagination bottom
        if (data.pagination_bottom && paginationBottom) {
          paginationBottom.innerHTML = data.pagination_bottom;
        }

        //Afficher les badges de filtres dynamiques
        if (data.filtersSummary) {
          const summary = document.querySelector(
            this.form.dataset.filtersTarget || "#filters-summary"
          );
          if (summary) {
            summary.innerHTML = data.filtersSummary;
          }
        }

        // Re-init UI après remplacement DOM
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
