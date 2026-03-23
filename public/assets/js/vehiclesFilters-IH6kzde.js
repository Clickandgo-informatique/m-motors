// vehiclesFilters-final.js
// JS minimaliste pour filtrage AJAX des véhicules depuis un fragment sidebar

export default class VehiclesFilter {
  constructor(container) {
    // Vérification du container
    if (!(container instanceof HTMLElement)) {
      console.warn(
        "[VehiclesFilter] container invalide, utilisation document.body"
      );
      container = document.body;
    }
    this.container = container;

    // Récupération du formulaire
    this.form = this.container.querySelector("[data-fetch-form]");
    if (!this.form) {
      console.error("[VehiclesFilter] Formulaire non trouvé dans le container");
      return;
    }

    this.fetchUrl = this.form.dataset.fetchUrl;
    if (!this.fetchUrl) {
      console.error(
        "[VehiclesFilter] data-fetch-url manquant sur le formulaire"
      );
      return;
    }

    // Cibles pour injection AJAX
    this.resultsTarget = this.container.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = this.container.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = this.container.querySelector(
      "[data-target='pagination-bottom']"
    );

    this.bindEvents();
    console.log("[VehiclesFilter] Initialisation terminée");
  }

  // Liaisons d’événements
  bindEvents() {
    // Soumission du formulaire
    this.form.addEventListener("change", () => this.submitFilters(1));

    // Pagination
    this.container.addEventListener("click", e => {
      const link = e.target.closest("[data-page]");
      if (link) {
        e.preventDefault();
        const page = parseInt(link.dataset.page);
        if (!isNaN(page)) this.submitFilters(page);
      }
    });
  }

  // Récupération des filtres et envoi AJAX
  async submitFilters(page = 1) {
    const formData = new FormData(this.form);
    const filters = {};

    // Construction de l’objet filters
    formData.forEach((val, key) => {
      const cleanKey = key.replace(/\[\]$/, ""); // supprime les [] pour les checkbox multiples
      if (filters[cleanKey])
        filters[cleanKey] = [].concat(filters[cleanKey], val);
      else filters[cleanKey] = [val];
    });

    console.log("[VehiclesFilter] Envoi AJAX", { page, filters });

    try {
      const res = await fetch(`${this.fetchUrl}?page=${page}`, {
        method: "POST",
        body: JSON.stringify({ filters }),
        headers: {
          "Content-Type": "application/json"
        }
      });

      const data = await res.json();

      // Injection dans le DOM
      if (this.resultsTarget) this.resultsTarget.innerHTML = data.results;
      if (this.paginationTopTarget)
        this.paginationTopTarget.innerHTML = data.paginationTop;
      if (this.paginationBottomTarget)
        this.paginationBottomTarget.innerHTML = data.paginationBottom;

      console.log("[VehiclesFilter] Résultats mis à jour");
    } catch (e) {
      console.error("[VehiclesFilter] Erreur AJAX :", e);
    }
  }
}

// Initialisation automatique pour le fragment sidebar existant
document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.querySelector("#sidebar"); // adapter l'ID du container si nécessaire
  if (sidebar) new VehiclesFilter(sidebar);
});
