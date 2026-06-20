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
    // Submit classique intercepté
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    let timeout = null;

    // Déclenche AJAX sur changement de filtres ou champs
    this.form.addEventListener("change", () => {
      if (!this.isReady) {
        return;
      }

      // IMPORTANT :
      // tout changement de filtre reset la pagination
      const pageInput = this.form.querySelector("input[name='page']");
      if (pageInput && !this.form.dataset.paginationAction) {
        pageInput.value = 1;
      }

      clearTimeout(timeout);

      timeout = setTimeout(() => {
        this.send();
      }, 120);
    });

    // Permet d’éviter les triggers initiaux
    setTimeout(() => {
      this.isReady = true;
    }, 300);
  }

  async send() {
    if (this.isLoading) {
      return;
    }

    // Annule requête précédente si nécessaire
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
      const formData = new FormData(this.form);
      const params = new URLSearchParams();

      formData.forEach((value, key) => {
        params.append(key, value);
      });

      const response = await fetch(`${url}?${params.toString()}`, {
        method: "GET",
        signal: this.abortController.signal
      });

      const data = await response.json();

      // Résultats
      if (data.results) {
        target.innerHTML = data.results;
      } else if (data.list) {
        target.innerHTML = data.list;
      }

      // Pagination haute
      const paginationTopSelector = this.form.dataset.paginationTop;

      if (paginationTopSelector && data.paginationTop) {
        const top = document.querySelector(paginationTopSelector);

        if (top) {
          top.innerHTML = data.paginationTop;
        }
      }

      // Pagination basse
      const paginationBottomSelector = this.form.dataset.paginationBottom;

      if (paginationBottomSelector && data.paginationBottom) {
        const bottom = document.querySelector(paginationBottomSelector);

        if (bottom) {
          bottom.innerHTML = data.paginationBottom;
        }
      }

      // Compat ancien format
      if (data.pagination) {
        const top = paginationTopSelector ? document.querySelector(paginationTopSelector) : null;

        const bottom = paginationBottomSelector
          ? document.querySelector(paginationBottomSelector)
          : null;

        if (top) {
          top.innerHTML = data.pagination;
        }

        if (bottom) {
          bottom.innerHTML = data.pagination;
        }
      }

      // Résumé filtres
      if (data.filtersSummary) {
        const summary = document.querySelector(
          this.form.dataset.filtersTarget || "#filters-summary"
        );

        if (summary) {
          summary.innerHTML = data.filtersSummary;
        }
      }

      // Reset flag pagination après usage
      delete this.form.dataset.paginationAction;

      // Hook global UI
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
