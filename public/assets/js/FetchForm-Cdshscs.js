/**
 * FetchForm
 * Version stable M-Motors
 *
 * Corrections principales :
 * - la pagination n’est plus écrasée par les events change
 * - les filtres mettent page=1 uniquement si action filtre réelle
 * - badges recalculés sur DOM stable
 * - suppression des conflits pagination/change
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
  }

  init() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    let timeout = null;

    /**
     * Gestion des changements (filtres uniquement)
     */
    this.form.addEventListener("change", e => {
      if (!this.isReady) {
        return;
      }

      /**
       * IMPORTANT :
       * Ne pas considérer la pagination comme un filtre
       */
      const isPagination = e.target.closest("[data-pagination]");

      const isInputChange = e.target.matches("input, select, textarea");

      /**
       * Reset page UNIQUEMENT si vrai filtre
       */
      if (isInputChange && !isPagination) {
        const pageInput = this.form.querySelector("input[name='page']");

        if (pageInput) {
          pageInput.value = 1;
        }
      }

      clearTimeout(timeout);

      timeout = setTimeout(() => {
        this.send();
      }, 120);
    });

    /**
     * Activation différée pour éviter triggers init DOM
     */
    setTimeout(() => {
      this.isReady = true;

      /**
       * Initialisation badges au chargement
       */
      window.__filterBadges?.updateBadges?.();
    }, 300);
  }

  async send() {
    if (this.isLoading) {
      return;
    }

    this.abortController?.abort();
    this.abortController = new AbortController();

    this.isLoading = true;
    this.form.dataset.loading = "1";

    const url = this.form.dataset.fetchUrl;

    const target = document.querySelector(this.form.dataset.target);

    if (!target) {
      this.isLoading = false;
      delete this.form.dataset.loading;
      return;
    }

    try {
      /**
       * Construction query string
       */
      const params = new URLSearchParams(new FormData(this.form));

      const response = await fetch(`${url}?${params.toString()}`, {
        method: "GET",
        signal: this.abortController.signal
      });

      const data = await response.json();

      /**
       * Render galerie
       */
      const html = data.list ?? data.results;

      if (html) {
        target.innerHTML = html;
      }

      /**
       * Pagination haute
       */
      if (this.form.dataset.paginationTop) {
        const top = document.querySelector(this.form.dataset.paginationTop);

        if (top) {
          top.innerHTML = data.paginationTop || data.pagination || "";
        }
      }

      /**
       * Pagination basse
       */
      if (this.form.dataset.paginationBottom) {
        const bottom = document.querySelector(this.form.dataset.paginationBottom);

        if (bottom) {
          bottom.innerHTML = data.paginationBottom || data.pagination || "";
        }
      }

      /**
       * Résumé filtres
       */
      if (data.filtersSummary) {
        const summary = document.querySelector(
          this.form.dataset.filtersTarget || "#filters-summary"
        );

        if (summary) {
          summary.innerHTML = data.filtersSummary;
        }
      }

      /**
       * UI refresh stable
       */
      requestAnimationFrame(() => {
        window.dispatchEvent(new Event("ui:updated"));
        window.__filterBadges?.updateBadges?.();
      });
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("[FetchForm]", e);
      }
    } finally {
      this.isLoading = false;
      delete this.form.dataset.loading;
    }
  }
}
