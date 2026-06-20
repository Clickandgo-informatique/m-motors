/**
 * FetchForm 
 * - 1 seul flux AJAX
 * - pas de rebind complexe
 * - badges + pagination déclenchés de manière synchrone
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
     * Submit classique intercepté
     */
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    let timeout = null;

    /**
     * Déclenche AJAX sur changement de filtres / search / sliders
     */
    this.form.addEventListener("change", () => {
      if (!this.isReady) {
        return;
      }

      /**
       * IMPORTANT :
       * reset page uniquement sur changement filtre/recherche
       * (la pagination ne passe pas par input change direct)
       */
      const pageInput = this.form.querySelector("input[name='page']");

      if (pageInput) {
        pageInput.value = 1;
      }

      clearTimeout(timeout);

      timeout = setTimeout(() => {
        this.send();
      }, 120);
    });

    /**
     * évite déclenchement initial DOM
     */
    setTimeout(() => {
      this.isReady = true;

      /**
       * INITIALISATION BADGES AU CHARGEMENT
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
      const params = new URLSearchParams(new FormData(this.form));

      const response = await fetch(`${url}?${params.toString()}`, {
        method: "GET",
        signal: this.abortController.signal
      });

      const data = await response.json();

      /**
       * Injection galerie véhicules
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
       * compatibilité ancienne pagination unique
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
       * résumé filtres actifs
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
       * FLUX UI STABLE
       */
      window.dispatchEvent(new Event("ui:updated"));

      window.__filterBadges?.updateBadges?.();
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
