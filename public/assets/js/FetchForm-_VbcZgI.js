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
     * Change event global (filtres + pagination indirecte)
     */
    this.form.addEventListener("change", () => {
      if (!this.isReady) return;

      clearTimeout(timeout);

      timeout = setTimeout(() => {
        this.send();
      }, 120);
    });

    /**
     * activation différée (évite triggers init DOM)
     */
    setTimeout(() => {
      this.isReady = true;

      /**
       * init badges au chargement
       */
      window.__filterBadges?.updateBadges?.();
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

    if (!target) {
      this.isLoading = false;
      delete this.form.dataset.loading;
      return;
    }

    try {
      /**
       * FORM DATA NATIF (SOURCE UNIQUE)
       */
      const formData = new FormData(this.form);

      // AJOUT EXPLICITE DES FILTRES HORS FORM
      document.querySelectorAll("input[name^='filters']").forEach(input => {
        if (input.type === "checkbox" || input.type === "radio") {
          if (input.checked) {
            formData.append(input.name, input.value);
          }
        } else {
          formData.append(input.name, input.value);
        }
      });
      const params = new URLSearchParams(formData);

      const response = await fetch(`${url}?${params.toString()}`, {
        method: "GET",
        signal: this.abortController.signal
      });

      const data = await response.json();

      /**
       * RESULTS
       */
      const html = data.list ?? data.results;

      if (html) {
        target.innerHTML = html;
      }

      /**
       * PAGINATION TOP
       */
      if (this.form.dataset.paginationTop) {
        const top = document.querySelector(this.form.dataset.paginationTop);

        if (top) {
          top.innerHTML = data.paginationTop || data.pagination || "";
        }
      }

      /**
       * PAGINATION BOTTOM
       */
      if (this.form.dataset.paginationBottom) {
        const bottom = document.querySelector(this.form.dataset.paginationBottom);

        if (bottom) {
          bottom.innerHTML = data.paginationBottom || data.pagination || "";
        }
      }

      /**
       * FILTERS SUMMARY (BADGES SOURCE BACKEND)
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
       * UI refresh global
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
