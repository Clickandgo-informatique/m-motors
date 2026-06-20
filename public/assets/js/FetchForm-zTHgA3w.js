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
    /**
     * Submit classique intercepté
     */
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    let timeout = null;

    /**
     * Change global (filtres + recherche + page)
     */
    this.form.addEventListener("change", () => {
      if (!this.isReady) {
        return;
      }

      const pageInput = this.form.querySelector("input[name='page']");

      /**
       * Reset page uniquement si ce n'est PAS une pagination
       */
      if (pageInput && !this.form._isPaginationEvent) {
        pageInput.value = 1;
      }

      /**
       * Reset flag pagination après utilisation
       */
      this.form._isPaginationEvent = false;

      clearTimeout(timeout);

      timeout = setTimeout(() => {
        this.send();
      }, 120);
    });

    /**
     * Evite triggers init DOM initial
     */
    setTimeout(() => {
      this.isReady = true;
    }, 300);
  }

  async send() {
    if (this.isLoading) {
      return;
    }

    /**
     * Annule requête précédente si encore active
     */
    this.abortController?.abort();
    this.abortController = new AbortController();

    this.isLoading = true;
    this.form.dataset.loading = "1";

    const url = this.form.dataset.fetchUrl;

    /**
     * Zone de rendu principale (galerie)
     */
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
       * Injection galerie
       * backend = "list"
       */
      const html = data.list ?? data.results;

      if (html) {
        target.innerHTML = html;
      } else {
        console.warn("[FetchForm] Aucun HTML reçu", data);
      }

      /**
       * Pagination haute
       */
      if (this.form.dataset.paginationTop && data.paginationTop) {
        const top = document.querySelector(this.form.dataset.paginationTop);

        if (top) {
          top.innerHTML = data.paginationTop;
        }
      }

      /**
       * Pagination basse
       */
      if (this.form.dataset.paginationBottom && data.paginationBottom) {
        const bottom = document.querySelector(this.form.dataset.paginationBottom);

        if (bottom) {
          bottom.innerHTML = data.paginationBottom;
        }
      }

      /**
       * Compat ancienne pagination unique
       */
      if (data.pagination) {
        const top = this.form.dataset.paginationTop
          ? document.querySelector(this.form.dataset.paginationTop)
          : null;

        const bottom = this.form.dataset.paginationBottom
          ? document.querySelector(this.form.dataset.paginationBottom)
          : null;

        if (top) top.innerHTML = data.pagination;
        if (bottom) bottom.innerHTML = data.pagination;
      }

      /**
       * Résumé filtres actifs
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
       * UI hooks globaux (badges, composants dynamiques)
       * exécuté après injection DOM
       */
      requestAnimationFrame(() => {
        window.__filterBadges?.updateBadges?.();
        window.dispatchEvent(new Event("ui:updated"));
      });
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("[FetchForm] Error:", e);
      }
    } finally {
      this.isLoading = false;
      delete this.form.dataset.loading;
    }
  }
}
