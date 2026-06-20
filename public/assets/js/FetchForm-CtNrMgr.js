/**
 * FetchForm
 * Version stabilisée (corrige pagination + badges)
 * - évite reset page sur pagination
 * - stabilise update badges après render
 * - garde logique AJAX simple et fiable
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
    /**
     * Interception submit classique
     */
    this.form.addEventListener("submit", (e) => {
      e.preventDefault();
      this.send();
    });

    let timeout = null;

    /**
     * Déclenche AJAX sur changement de filtres / sliders / search
     */
    this.form.addEventListener("change", (e) => {
      if (!this.isReady) {
        return;
      }

      /**
       * IMPORTANT :
       * On ne reset la page QUE si le changement ne vient pas de la pagination
       */
      const isPagination = e.target.closest("[data-pagination]");

      if (!isPagination) {
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
     * Empêche les triggers initiaux du DOM
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

    /**
     * Annule requête précédente si nécessaire
     */
    this.abortController?.abort();
    this.abortController = new AbortController();

    this.isLoading = true;
    this.form.dataset.loading = "1";

    const url = this.form.dataset.fetchUrl;

    /**
     * Zone principale (galerie véhicules)
     */
    const target = document.querySelector(this.form.dataset.target);

    if (!target) {
      this.isLoading = false;
      delete this.form.dataset.loading;
      return;
    }

    try {
      /**
       * Construction query string depuis le form
       */
      const params = new URLSearchParams(new FormData(this.form));

      const response = await fetch(`${url}?${params.toString()}`, {
        method: "GET",
        signal: this.abortController.signal
      });

      const data = await response.json();

      /**
       * Injection galerie
       */
      const html = data.list ?? data.results;

      if (html) {
        target.innerHTML = html;
      }

      /**
       * Pagination haute
       */
      if (this.form.dataset.paginationTop && data.paginationTop !== undefined) {
        const top = document.querySelector(this.form.dataset.paginationTop);

        if (top) {
          top.innerHTML = data.paginationTop || "";
        }
      }

      /**
       * Pagination basse
       */
      if (this.form.dataset.paginationBottom && data.paginationBottom !== undefined) {
        const bottom = document.querySelector(this.form.dataset.paginationBottom);

        if (bottom) {
          bottom.innerHTML = data.paginationBottom || "";
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
       * UI update stable
       * - badges recalculés après DOM injection
       * - event global pour autres modules
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