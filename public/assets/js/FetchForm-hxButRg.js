export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;

    this.isLoading = false;
    this.abortController = new AbortController();

    this.init();
  }

  init() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    let timeout = null;

    this.form.addEventListener("change", () => {
      clearTimeout(timeout);

      timeout = setTimeout(() => {
        this.send();
      }, 100);
    });
  }

  async send() {
    if (this.isLoading) return;

    // Abort requête précédente si encore en cours
    if (this.abortController) {
      this.abortController.abort();
    }

    this.abortController = new AbortController();

    this.isLoading = true;
    this.form.dataset.loading = "1";

    const url = this.form.dataset.fetchUrl;

    const target = document.querySelector(this.form.dataset.target);

    // IMPORTANT : on cible les 2 zones pagination explicitement
    const paginationTop = document.querySelector("#vehicles-pagination-top");
    const paginationBottom = document.querySelector("#vehicles-pagination-bottom");

    if (!target) {
      console.error("[FetchForm] Target missing");
      this.isLoading = false;
      return;
    }

    try {
      const params = new URLSearchParams(new FormData(this.form));

      const res = await fetch(`${url}?${params.toString()}`, {
        method: "GET",
        signal: this.abortController.signal
      });

      if (!res.ok) {
        console.error("[FetchForm] HTTP error", res.status);
        return;
      }

      const data = await res.json();

      // Liste véhicules
      if (data.list) {
        target.innerHTML = data.list;
      }

      // Pagination (sync TOP + BOTTOM)
      if (data.pagination) {
        if (paginationTop) {
          paginationTop.innerHTML = data.pagination;
        }

        if (paginationBottom) {
          paginationBottom.innerHTML = data.pagination;
        }
      }

      // Résumé filtres
      if (data.filtersSummary) {
        const summary = document.querySelector("#filters-summary");
        if (summary) summary.innerHTML = data.filtersSummary;
      }

      // Trigger global UI refresh
      window.dispatchEvent(new Event("ui:updated"));
    } catch (err) {
      if (err.name !== "AbortError") {
        console.error("[FetchForm] erreur AJAX", err);
      }
    } finally {
      this.isLoading = false;
      delete this.form.dataset.loading;
    }
  }
}
