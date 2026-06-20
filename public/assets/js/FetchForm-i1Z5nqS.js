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
    // Interception submit classique
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    let timeout = null;

    // Déclenchement AJAX sur changement de champs (filtres + search + page)
    this.form.addEventListener("change", () => {
      if (!this.isReady) {
        return;
      }

      // Reset pagination à chaque changement de filtre
      const pageInput = this.form.querySelector("input[name='page']");
      if (pageInput) {
        pageInput.value = 1;
      }

      clearTimeout(timeout);

      timeout = setTimeout(() => {
        this.send();
      }, 120);
    });

    // Évite les triggers initiaux au chargement de la page
    setTimeout(() => {
      this.isReady = true;
    }, 300);
  }

  async send() {
    if (this.isLoading) {
      return;
    }

    // Annule une requête précédente si elle est encore en cours
    this.abortController?.abort();
    this.abortController = new AbortController();

    this.isLoading = true;
    this.form.dataset.loading = "1";

    const url = this.form.dataset.fetchUrl;

    // Zone principale de rendu (galerie véhicules)
    const target = document.querySelector(this.form.dataset.target);

    if (!target) {
      this.isLoading = false;
      delete this.form.dataset.loading;
      return;
    }

    try {
      // Construction query string depuis le formulaire
      const params = new URLSearchParams(new FormData(this.form));

      const response = await fetch(`${url}?${params.toString()}`, {
        method: "GET",
        signal: this.abortController.signal
      });

      const data = await response.json();

      // Injection de la galerie
      // IMPORTANT : backend utilise "list"
      const html = data.list ?? data.results;

      if (html) {
        target.innerHTML = html;
      } else {
        console.warn("[FetchForm] Aucun HTML reçu pour la liste", data);
      }

      // Pagination haute
      if (this.form.dataset.paginationTop && data.paginationTop) {
        const top = document.querySelector(this.form.dataset.paginationTop);

        if (top) {
          top.innerHTML = data.paginationTop;
        }
      }

      // Pagination basse
      if (this.form.dataset.paginationBottom && data.paginationBottom) {
        const bottom = document.querySelector(this.form.dataset.paginationBottom);

        if (bottom) {
          bottom.innerHTML = data.paginationBottom;
        }
      }

      // Compatibilité ancien format pagination unique
      if (data.pagination) {
        const top = this.form.dataset.paginationTop
          ? document.querySelector(this.form.dataset.paginationTop)
          : null;

        const bottom = this.form.dataset.paginationBottom
          ? document.querySelector(this.form.dataset.paginationBottom)
          : null;

        if (top) {
          top.innerHTML = data.pagination;
        }

        if (bottom) {
          bottom.innerHTML = data.pagination;
        }
      }

      // Résumé des filtres actifs
      if (data.filtersSummary) {
        const summary = document.querySelector(
          this.form.dataset.filtersTarget || "#filters-summary"
        );

        if (summary) {
          summary.innerHTML = data.filtersSummary;
        }
      }

      // Hook global pour réinitialiser les composants UI (badges, listeners, etc.)
      window.dispatchEvent(new Event("ui:updated"));

      // Mise à jour éventuelle des badges de filtres
      window.__filterBadges?.updateBadges?.();
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("[FetchForm] Error:", e);
      }
    } finally {
      this.isLoading = false;
      delete this.form.dataset.loading;
    }
  }
}
