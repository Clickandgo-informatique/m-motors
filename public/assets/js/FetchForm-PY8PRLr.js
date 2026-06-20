import initPagination from "./Pagination.js";

/**
 * FetchForm
 * - Gère filtres + recherche + pagination AJAX
 * - Une seule requête contrôlée à la fois
 * - Toute la configuration provient du conteneur [data-listing]
 */
export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    this.form = form;
    this.listing = form.closest("[data-listing]");

    this.isLoading = false;
    this.abortController = new AbortController();
    this.isReady = false;

    this.init();

    // Initialise la pagination une seule fois
    initPagination();
  }

  init() {
    // Soumission classique en fallback
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    let timeout = null;

    // Déclenchement automatique lors d'un changement de filtre
    this.form.addEventListener("change", () => {
      if (!this.isReady) return;

      // Ignore les événements provenant de la pagination
      if (this.form._fromPagination) return;

      clearTimeout(timeout);

      timeout = setTimeout(() => {
        this.send();
      }, 120);
    });

    // Évite un déclenchement prématuré au chargement de la page
    setTimeout(() => {
      this.isReady = true;
    }, 300);
  }

  async send() {
    if (this.isLoading) return;

    this.abortController?.abort();
    this.abortController = new AbortController();

    this.isLoading = true;
    this.form.dataset.loading = "1";

    // Toute la configuration AJAX est portée par le conteneur data-listing
    if (!this.listing) {
      this.isLoading = false;
      delete this.form.dataset.loading;
      return;
    }

    const url = this.listing.dataset.fetchUrl;
    const target = document.querySelector(this.listing.dataset.target);

    if (!url || !target) {
      this.isLoading = false;
      delete this.form.dataset.loading;
      return;
    }

    try {
      const formData = new FormData(this.form);

      // on ajoute les inputs de la sidebar (source UI externe)
      const listing = this.form.closest("[data-listing]");
      const externalInputs = listing.querySelectorAll("aside input, aside select");

      externalInputs.forEach(input => {
        if (!input.name) return;
        formData.set(input.name, input.value);
      });
      
      const params = new URLSearchParams();

      // Retour à la première page lors d'un changement de filtre
      if (!this.form._fromPagination) {
        const pageInput = this.form.querySelector("input[name='page']");

        if (pageInput) {
          pageInput.value = 1;
        }
      }

      // Construction des paramètres GET
      formData.forEach((value, key) => {
        if (value !== null && value !== "") {
          params.append(key, value);
        }
      });

      // Synchronise l'URL du navigateur
      const browserUrl = `${window.location.pathname}?${params.toString()}`;
      window.history.replaceState({}, "", browserUrl);

      const response = await fetch(`${url}?${params.toString()}`, {
        method: "GET",
        signal: this.abortController.signal,
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      const data = await response.json();

      // Mise à jour de la liste
      if (data.list) {
        target.innerHTML = data.list;
      }

      // Mise à jour de la pagination du haut
      if (this.listing.dataset.paginationTop && data.paginationTop) {
        const top = document.querySelector(this.listing.dataset.paginationTop);

        if (top) {
          top.innerHTML = data.paginationTop;
        }
      }

      // Mise à jour de la pagination du bas
      if (this.listing.dataset.paginationBottom && data.paginationBottom) {
        const bottom = document.querySelector(this.listing.dataset.paginationBottom);

        if (bottom) {
          bottom.innerHTML = data.paginationBottom;
        }
      }

      // Mise à jour du résumé des filtres
      if (this.listing.dataset.summary && data.filtersSummary) {
        const summary = document.querySelector(this.listing.dataset.summary);

        if (summary) {
          summary.innerHTML = data.filtersSummary;
        }
      }

      // Réinitialisation des composants dynamiques
      window.dispatchEvent(new Event("ui:updated"));
      window.__filterBadges?.updateBadges();
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("[FetchForm]", e);
      }
    } finally {
      this.isLoading = false;
      delete this.form.dataset.loading;

      // Réinitialise le flag utilisé par la pagination
      this.form._fromPagination = false;
    }
  }
}
