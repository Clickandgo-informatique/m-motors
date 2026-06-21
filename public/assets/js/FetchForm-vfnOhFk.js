export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    this.form = form;

    this.isLoading = false;
    this.abortController = null;

    this._timeout = null;

    this.lastTrigger = "submit";

    this.init();
  }

  init() {
    // soumission classique du formulaire (clic sur bouton rechercher)
    this.form.addEventListener("submit", e => {
      e.preventDefault();

      this.lastTrigger = "submit";

      this.send();
    });

    // déclenchement automatique lors des changements de filtres
    this.form.addEventListener("change", e => {
      if (!this.form.contains(e.target)) return;

      this.lastTrigger = "filter";

      this.scheduleSend();
    });

    // déclenchement automatique lors de la saisie
    this.form.addEventListener("input", e => {
      if (!this.form.contains(e.target)) return;

      this.lastTrigger = "filter";

      this.scheduleSend();
    });

    // bouton reset filtres
    this.initResetButton();
  }

  initResetButton() {
    const resetBtn = this.form.querySelector("[data-reset-filters]");

    if (!resetBtn) {
      return;
    }

    resetBtn.addEventListener("click", e => {
      e.preventDefault();

      this.resetFilters();

      this.lastTrigger = "reset";

      // on repasse par submit pour garder un flux cohérent
      this.form.dispatchEvent(new Event("submit", { bubbles: true, cancelable: true }));
    });
  }

  resetFilters() {
    // reset natif des champs visibles
    this.form.reset();

    // reset pagination uniquement
    const pageInput = this.form.querySelector('[name="page"]');

    if (pageInput) {
      pageInput.value = 1;
    }

    // nettoyage des hidden liés aux filtres (sans toucher aux champs techniques)
    this.form.querySelectorAll('input[type="hidden"]').forEach(input => {
      if (input.name !== "page") {
        input.value = "";
      }
    });
  }

  scheduleSend() {
    clearTimeout(this._timeout);

    this._timeout = setTimeout(() => {
      this.send();
    }, 120);
  }

  async send() {
    // empêche les requêtes concurrentes
    if (this.isLoading) {
      return;
    }

    // annule la requête précédente si elle existe
    if (this.abortController) {
      this.abortController.abort();
    }

    this.abortController = new AbortController();

    this.isLoading = true;
    this.form.dataset.loading = "1";

    const url = this.form.dataset.fetchUrl;
    const target = document.querySelector(this.form.dataset.target);

    if (!url || !target) {
      this.cleanup();
      return;
    }

    // sécurité : si reset, forcer page à 1 avant construction requête
    if (this.lastTrigger === "reset") {
      const pageInput = this.form.querySelector('[name="page"]');

      if (pageInput) {
        pageInput.value = 1;
      }
    }

    try {
      const formData = new FormData(this.form);
      const params = new URLSearchParams();

      // construction query string propre
      formData.forEach((value, key) => {
        if (value !== null && value !== "") {
          params.append(key, value);
        }
      });

      const response = await fetch(`${url}?${params.toString()}`, {
        method: "GET",
        signal: this.abortController.signal
      });

      const data = await response.json();

      // rendu des résultats
      this.renderTarget(target, data);

      // rendu pagination
      this.renderPagination(data);

      // résumé des filtres
      this.renderFiltersSummary(data);

      // event global UI
      window.dispatchEvent(new Event("ui:updated"));

      // update badges filtres si existant
      if (window.__filterBadges?.updateBadges) {
        window.__filterBadges.updateBadges();
      }
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("[FetchForm]", e);
      }
    } finally {
      this.cleanup();
    }
  }

  renderTarget(target, data) {
    if (data.results) {
      target.innerHTML = data.results;
      return;
    }

    if (data.list) {
      target.innerHTML = data.list;
    }
  }

  renderPagination(data) {
    const topSelector = this.form.dataset.paginationTop;
    const bottomSelector = this.form.dataset.paginationBottom;

    const top = topSelector ? document.querySelector(topSelector) : null;
    const bottom = bottomSelector ? document.querySelector(bottomSelector) : null;

    // pagination spécifique haut/bas
    if (data.paginationTop && top) {
      top.innerHTML = data.paginationTop;
    }

    if (data.paginationBottom && bottom) {
      bottom.innerHTML = data.paginationBottom;
    }

    // fallback pagination unique
    if (data.pagination) {
      if (top) {
        top.innerHTML = data.pagination;
      }

      if (bottom) {
        bottom.innerHTML = data.pagination;
      }
    }
  }

  renderFiltersSummary(data) {
    if (!data.filtersSummary) {
      return;
    }

    const target = document.querySelector(this.form.dataset.filtersTarget || "#filters-summary");

    if (target) {
      target.innerHTML = data.filtersSummary;
    }
  }

  cleanup() {
    this.isLoading = false;

    delete this.form.dataset.loading;
  }
}
