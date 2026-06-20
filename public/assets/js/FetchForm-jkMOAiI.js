import initPagination from "./Pagination.js";

/**
 * FetchForm
 * - Gère filtres + recherche + pagination AJAX
 * - Une seule requête contrôlée à la fois
 */
export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    this.form = form;
    this.isLoading = false;
    this.abortController = new AbortController();
    this.isReady = false;

    this.init();

    // Initialise la pagination une seule fois (important)
    initPagination();
  }

  init() {
    // Soumission classique fallback
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    let timeout = null;

    // Déclenchement automatique via filtres
    this.form.addEventListener("change", () => {
      if (!this.isReady) return;

      // Ne pas déclencher sur pagination
      if (this.form._fromPagination) return;

      clearTimeout(timeout);

      timeout = setTimeout(() => {
        this.send();
      }, 120);
    });

    setTimeout(() => {
      this.isReady = true;
    }, 300);
  }

  async send() {
    if (this.isLoading) return;

    this.abortController?.abort();
    this.abortController = new AbortController();

    this.isLoading = true;
    this.form.dataset.loading = "1";

    const url = this.form.dataset.fetchUrl;
    const target = document.querySelector(this.form.dataset.target);

    if (!url || !target) {
      this.isLoading = false;
      delete this.form.dataset.loading;
      return;
    }

    try {
      const formData = new FormData(this.form);
      const params = new URLSearchParams();

      // Reset page uniquement si action filtre
      if (!this.form._fromPagination) {
        const pageInput = this.form.querySelector("input[name='page']");

        if (pageInput) {
          pageInput.value = 1;
        }
      }

      formData.forEach((value, key) => {
        if (value !== null && value !== "") {
          params.append(key, value);
        }
      });

      const browserUrl = `${window.location.pathname}?${params.toString()}`;
      window.history.replaceState({}, "", browserUrl);

      const response = await fetch(`${url}?${params.toString()}`, {
        method: "GET",
        signal: this.abortController.signal,
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      const data = await response.json();

      // Listing véhicules
      if (data.list) {
        target.innerHTML = data.list;
      }

      // Pagination top
      const topSelector = this.form.dataset.paginationTop;

      if (topSelector && data.paginationTop) {
        const top = document.querySelector(topSelector);

        if (top) {
          top.innerHTML = data.paginationTop;
        }
      }

      // Pagination bottom
      const bottomSelector = this.form.dataset.paginationBottom;

      if (bottomSelector && data.paginationBottom) {
        const bottom = document.querySelector(bottomSelector);

        if (bottom) {
          bottom.innerHTML = data.paginationBottom;
        }
      }

      // Summary filtres
      const summarySelector = this.form.dataset.filtersTarget || "#filters-summary";

      if (data.filtersSummary) {
        const summary = document.querySelector(summarySelector);

        if (summary) {
          summary.innerHTML = data.filtersSummary;
        }
      }

      window.dispatchEvent(new Event("ui:updated"));
      window.__filterBadges?.updateBadges();
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("[FetchForm]", e);
      }
    } finally {
      this.isLoading = false;
      delete this.form.dataset.loading;

      // Reset flag propre
      this.form._fromPagination = false;
    }
  }
}
