export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    this.form = form;
    this.isLoading = false;
    this.abortController = new AbortController();

    // Empêche les déclenchements pendant l'initialisation
    this.isReady = false;

    this.init();
  }

  init() {
    // Soumission classique du formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    let timeout = null;

    // Déclenchement automatique lors d'un changement
    this.form.addEventListener("change", () => {
      if (!this.isReady) {
        return;
      }

      clearTimeout(timeout);

      timeout = setTimeout(() => {
        this.send();
      }, 120);
    });

    // Activation après chargement du composant
    setTimeout(() => {
      this.isReady = true;
    }, 300);
  }

  async send() {
    if (this.isLoading) {
      return;
    }

    // Annule une requête précédente encore en cours
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
      // Construction des paramètres GET à partir du formulaire
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

      // Mise à jour de la zone principale des résultats
      if (data.results) {
        target.innerHTML = data.results;
      } else if (data.list) {
        target.innerHTML = data.list;
      }

      // Mise à jour de la pagination du haut
      const paginationTopSelector = this.form.dataset.paginationTop;

      if (paginationTopSelector && data.paginationTop) {
        const top = document.querySelector(paginationTopSelector);

        if (top) {
          top.innerHTML = data.paginationTop;
        }
      }

      // Mise à jour de la pagination du bas
      const paginationBottomSelector = this.form.dataset.paginationBottom;

      if (paginationBottomSelector && data.paginationBottom) {
        const bottom = document.querySelector(paginationBottomSelector);

        if (bottom) {
          bottom.innerHTML = data.paginationBottom;
        }
      }

      // Compatibilité avec les anciens contrôleurs
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

      // Mise à jour éventuelle du résumé des filtres
      if (data.filtersSummary) {
        const summary = document.querySelector(
          this.form.dataset.filtersTarget || "#filters-summary"
        );

        if (summary) {
          summary.innerHTML = data.filtersSummary;
        }
      }

      // Réinitialisation des composants qui doivent être réattachés
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
