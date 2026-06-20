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
    this.form.addEventListener("submit", (e) => {
      e.preventDefault();
      this.send();
    });

    let timeout = null;

    /**
     * Gestion des changements filtres + pagination
     */
    this.form.addEventListener("change", (e) => {
      if (!this.isReady) {
        return;
      }

      const isPagination = e.target.closest("[data-pagination]");

      /**
       * Reset page uniquement si filtre (pas pagination)
       */
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
     * Activation différée pour éviter triggers init DOM
     */
    setTimeout(() => {
      this.isReady = true;

      /**
       * badges init
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
       * FORM DATA ROBUSTE (IMPORTANT FIX)
       * - inclut inputs dynamiques
       * - garantit cohérence sliders + checkboxes
       */
      const formData = new FormData();

      this.form.querySelectorAll("input, select, textarea").forEach(el => {
        if (!el.name) return;

        if (el.type === "checkbox") {
          if (el.checked) {
            formData.append(el.name, el.value);
          }
          return;
        }

        if (el.type === "radio") {
          if (el.checked) {
            formData.set(el.name, el.value);
          }
          return;
        }

        formData.set(el.name, el.value);
      });

      const params = new URLSearchParams(formData);

      const response = await fetch(`${url}?${params.toString()}`, {
        method: "GET",
        signal: this.abortController.signal
      });

      const data = await response.json();

      /**
       * rendu galerie
       */
      const html = data.list ?? data.results;

      if (html) {
        target.innerHTML = html;
      }

      /**
       * pagination
       */
      if (this.form.dataset.paginationTop) {
        const top = document.querySelector(this.form.dataset.paginationTop);
        if (top) top.innerHTML = data.paginationTop || data.pagination || "";
      }

      if (this.form.dataset.paginationBottom) {
        const bottom = document.querySelector(this.form.dataset.paginationBottom);
        if (bottom) bottom.innerHTML = data.paginationBottom || data.pagination || "";
      }

      /**
       * filters summary
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