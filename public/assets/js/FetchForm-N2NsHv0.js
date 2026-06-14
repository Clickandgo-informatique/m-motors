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
      if (!this.isReady) {
        return;
      }

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
    if (this.isLoading) {
      return;
    }

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
      const params = new URLSearchParams();

      formData.forEach((value, key) => {
        params.append(key, value);
      });

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

      // Mise à jour pagination haute
      const paginationTopSelector = this.form.dataset.paginationTop;

      if (paginationTopSelector && data.paginationTop) {
        const top = document.querySelector(paginationTopSelector);

        if (top) {
          top.innerHTML = data.paginationTop;
        }
      }

      // Mise à jour pagination basse
      const paginationBottomSelector = this.form.dataset.paginationBottom;

      if (paginationBottomSelector && data.paginationBottom) {
        const bottom = document.querySelector(paginationBottomSelector);

        if (bottom) {
          bottom.innerHTML = data.paginationBottom;
        }
      }

      // Compatibilité avec anciens formats de réponse
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
