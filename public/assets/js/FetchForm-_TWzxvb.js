import initPagination from "./Pagination.js";

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
    // Empêche le submit classique et déclenche la requête AJAX
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    let timeout = null;

    // Déclenche une requête lors des changements de champs
    this.form.addEventListener("change", () => {
      if (!this.isReady) return;

      clearTimeout(timeout);

      timeout = setTimeout(() => {
        this.send();
      }, 120);
    });

    // Petit délai pour éviter les triggers initiaux
    setTimeout(() => {
      this.isReady = true;
    }, 300);
  }

  async send() {
    if (this.isLoading) return;

    // Annule une requête précédente si encore en cours
    this.abortController?.abort();
    this.abortController = new AbortController();

    this.isLoading = true;
    this.form.dataset.loading = "1";

    const url = this.form.dataset.fetchUrl;

    // Zone de rendu principale
    const target = document.querySelector(this.form.dataset.target);

    if (!target) {
      this.isLoading = false;
      delete this.form.dataset.loading;
      return;
    }

    try {
      // Construction des paramètres GET depuis le formulaire
      const formData = new FormData(this.form);

      // FORCER reset page si changement autre que pagination
      if (!this._fromPagination) {
        const pageInput = this.form.querySelector("input[name='page']");
        if (pageInput) {
          pageInput.value = 1;
        }
      }

      const params = new URLSearchParams();

      window.history.pushState({}, "", browserUrl);

      formData.forEach((value, key) => {
        params.append(key, value);
      });

      // Synchronise l'URL du navigateur
      const newUrl = `${window.location.pathname}?${params.toString()}`;
      window.history.replaceState({}, "", newUrl);

      const response = await fetch(`${url}?${params.toString()}`, {
        method: "GET",
        signal: this.abortController.signal
      });

      const data = await response.json();

      // Mise à jour des résultats
      if (data.results) {
        target.innerHTML = data.results;
      } else if (data.list) {
        target.innerHTML = data.list;
      }

      // Sélecteurs pagination
      const paginationTopSelector = this.form.dataset.paginationTop;
      const paginationBottomSelector = this.form.dataset.paginationBottom;

      // Pagination haute
      if (paginationTopSelector && data.paginationTop) {
        const top = document.querySelector(paginationTopSelector);
        if (top) {
          top.innerHTML = data.paginationTop;
          initPagination(top); // ← ré‑initialisation
        }
      }

      // Pagination basse
      if (paginationBottomSelector && data.paginationBottom) {
        const bottom = document.querySelector(paginationBottomSelector);
        if (bottom) {
          bottom.innerHTML = data.paginationBottom;
          initPagination(bottom); // ← ré‑initialisation
        }
      }

      // Compatibilité anciens formats
      if (data.pagination) {
        const top = paginationTopSelector ? document.querySelector(paginationTopSelector) : null;
        const bottom = paginationBottomSelector
          ? document.querySelector(paginationBottomSelector)
          : null;

        if (top) {
          top.innerHTML = data.pagination;
          initPagination(top);
        }

        if (bottom) {
          bottom.innerHTML = data.pagination;
          initPagination(bottom);
        }
      }

      // Mise à jour des filtres si nécessaire
      if (data.filtersSummary) {
        const summary = document.querySelector(
          this.form.dataset.filtersTarget || "#filters-summary"
        );

        if (summary) {
          summary.innerHTML = data.filtersSummary;
        }
      }

      // Déclenchement global pour réinitialisation des composants UI
      window.dispatchEvent(new Event("ui:updated"));

      // Mise à jour éventuelle des badges de filtres
      window.__filterBadges?.updateBadges();
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
