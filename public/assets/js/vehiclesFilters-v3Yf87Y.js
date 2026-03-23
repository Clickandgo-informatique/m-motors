// VehiclesFilter.js
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(containerSelector = "[data-fetch-form]") {
    // Récupération du container parent du formulaire
    this.container = document.querySelector(containerSelector);
    if (!this.container) return; // arrêt si container inexistant

    // Récupération du formulaire à l'intérieur du container
    this.form = this.container.querySelector("form");
    if (!this.form) return; // arrêt si formulaire inexistant

    // URL de l'endpoint AJAX
    this.fetchUrl = this.form.dataset.fetchUrl;
    if (!this.fetchUrl) return;

    // Targets pour injection des résultats et pagination
    this.resultsTarget = this.container.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = this.container.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = this.container.querySelector(
      "[data-target='pagination-bottom']"
    );

    // Timeout pour le debounce
    this.debounceTimeout = null;

    // Initialisation des sliders présents
    this.initSliders();

    // Bind des événements du formulaire et de la pagination
    this.bindEvents();
  }

  // Initialisation des sliders (double-slider)
  initSliders() {
    this.container.querySelectorAll(".double-slider").forEach(slider => {
      initDoubleSlider(slider);

      // Déclenchement de la soumission AJAX lorsque le slider change
      slider.addEventListener("sliderChanged", () => this.debounceSubmit());
    });
  }

  // Délégation des événements pour inputs, selects et pagination
  bindEvents() {
    // Soumission du formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.debounceSubmit();
    });

    // Changement sur inputs et selects via délégation
    this.container.addEventListener("change", e => {
      if (e.target.matches("input, select")) this.debounceSubmit();
    });

    // Pagination via delegation sur le container
    this.container.addEventListener("click", e => {
      const link = e.target.closest("[data-page]");
      if (link) {
        e.preventDefault();
        const page = parseInt(link.dataset.page);
        this.debounceSubmit(page);
      }
    });
  }

  // Debounce pour éviter les appels AJAX trop fréquents
  debounceSubmit(page = 1, delay = 200) {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(page), delay);
  }

  // Soumission AJAX des filtres
  async submitFilters(page = 1) {
    if (!this.fetchUrl) return;

    // Récupération des valeurs du formulaire
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
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      const data = await res.json();

      // Injection des résultats et pagination
      if (this.resultsTarget) this.resultsTarget.innerHTML = data.results;
      if (this.paginationTopTarget)
        this.paginationTopTarget.innerHTML = data.paginationTop;
      if (this.paginationBottomTarget)
        this.paginationBottomTarget.innerHTML = data.paginationBottom;

      // Réinitialisation des sliders après injection AJAX
      this.initSliders();

      // Scroll vers les résultats
      if (this.resultsTarget) {
        this.resultsTarget.scrollIntoView({
          behavior: "smooth",
          block: "start"
        });
      }
    } catch (e) {
      console.error("VehiclesFilter AJAX error", e);
    }
  }
}

// Initialisation après chargement complet du DOM
document.addEventListener("DOMContentLoaded", () => {
  new VehiclesFilter(); // instanciation sécurisée
});
