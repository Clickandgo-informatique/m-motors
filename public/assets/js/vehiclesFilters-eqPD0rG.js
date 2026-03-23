// vehiclesFiltersSummary.js
// Gestion du formulaire de filtres véhicules avec résumé des filtres utilisés
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(container) {
    if (!container) return;

    // Conteneur parent injecté
    this.container = container;

    // Formulaire à l'intérieur du container
    this.form = container.querySelector("#filters-form");
    if (!this.form) {
      console.warn("VehiclesFilter : formulaire introuvable");
      return;
    }

    // Targets dynamiques
    this.resultsTarget = container.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = container.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = container.querySelector(
      "[data-target='pagination-bottom']"
    );
    this.filtersSummaryTarget = container.querySelector(
      "[data-target='filters-summary']"
    );

    this.fetchUrl = this.form.dataset.fetchUrl;
    this.debounceTimeout = null;

    // Initialisation
    this.initSliders();
    this.bindEvents();
  }

  // Initialise tous les doublesliders dans le formulaire
  initSliders() {
    this.form.querySelectorAll(".double-slider").forEach(slider => {
      initDoubleSlider(slider);

      // Écoute de l'événement sliderChanged pour soumettre le filtre
      slider.addEventListener("sliderChanged", () => this.debounceSubmit());
    });
  }

  // Lie tous les événements nécessaires
  bindEvents() {
    // Soumission du formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.debounceSubmit();
    });

    // Inputs et selects
    this.form.querySelectorAll("input, select").forEach(el => {
      el.addEventListener("change", () => this.debounceSubmit());
    });

    // Pagination
    document.addEventListener("click", e => {
      const link = e.target.closest(".pagination a[data-page]");
      if (link) {
        e.preventDefault();
        const page = Number.parseInt(link.dataset.page);
        this.debounceSubmit(page);
      }
    });
  }

  // Debounce pour éviter trop de requêtes
  debounceSubmit(page = 1, delay = 150) {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(page), delay);
  }

  // Soumet les filtres via AJAX
  async submitFilters(page = 1) {
    if (!this.fetchUrl) return;

    // Récupère toutes les valeurs du formulaire
    const formData = new FormData(this.form);
    const filters = {};
    formData.forEach((val, key) => {
      if (filters[key]) filters[key] = [].concat(filters[key], val);
      else filters[key] = val;
    });

    try {
      const res = await fetch(`${this.fetchUrl}?page=${page}`, {
        method: "POST",
        body: JSON.stringify({ filters, q: filters.q || null }),
        headers: {
          "Content-Type": "application/json"
        }
      });

      const data = await res.json();

      // Injection des résultats
      if (this.resultsTarget) this.resultsTarget.innerHTML = data.results;
      if (this.paginationTopTarget)
        this.paginationTopTarget.innerHTML = data.paginationTop;
      if (this.paginationBottomTarget)
        this.paginationBottomTarget.innerHTML = data.paginationBottom;

      // Injection du résumé des filtres utilisés
      if (this.filtersSummaryTarget && data.filtersSummary) {
        this.filtersSummaryTarget.innerHTML = data.filtersSummary;
      }
    } catch (e) {
      console.error("VehiclesFilter AJAX error", e);
    }
  }
}
