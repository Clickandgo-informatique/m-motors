// VehiclesFilter.js
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor() {
    // On récupère le formulaire
    this.form = document.querySelector("[data-fetch-form]");
    if (!this.form) return; // Stop si formulaire introuvable

    // Container parent du formulaire pour délégation
    this.container =
      this.form.closest("[data-fetch-form-container]") || document.body;

    // URL de l'endpoint AJAX
    this.fetchUrl = this.form.dataset.fetchUrl;
    if (!this.fetchUrl) return;

    // Cibles pour injection des résultats et pagination
    this.resultsTarget = this.container.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = this.container.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = this.container.querySelector(
      "[data-target='pagination-bottom']"
    );

    // Timeout pour debounce
    this.debounceTimeout = null;

    // Initialisation sliders et événements
    this.initSliders();
    this.bindEvents();
  }

  /**
   * Initialise les sliders et écoute leur événement
   */
  initSliders() {
    this.container.querySelectorAll(".double-slider").forEach(slider => {
      initDoubleSlider(slider);

      // Quand le slider change, déclenche la recherche
      slider.addEventListener("sliderChanged", e => {
        // On met à jour les valeurs min/max dans les filtres
        const filterName = slider.dataset.filter;
        this.form.querySelector(`#${filterName}-min-value`)?.textContent;
        this.form.querySelector(`#${filterName}-max-value`)?.textContent;

        this.debounceSubmit();
      });
    });
  }

  /**
   * Bind des événements :
   * - soumission du formulaire
   * - changement d'input ou select
   * - clic sur pagination
   */
  bindEvents() {
    // Soumission du formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.debounceSubmit();
    });

    // Changement sur input/select
    this.container.addEventListener("change", e => {
      if (e.target.matches("input, select")) {
        this.debounceSubmit();
      }
    });

    // Pagination via delegation
    this.container.addEventListener("click", e => {
      const link = e.target.closest("[data-page]");
      if (link) {
        e.preventDefault();
        const page = parseInt(link.dataset.page);
        if (!isNaN(page)) this.debounceSubmit(page);
      }
    });
  }

  /**
   * Debounce pour limiter les appels AJAX rapides
   */
  debounceSubmit(page = 1, delay = 200) {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(page), delay);
  }

  /**
   * Envoie la requête AJAX
   */
  async submitFilters(page = 1) {
    if (!this.fetchUrl) return;

    const formData = new FormData(this.form);
    const filters = {};

    // Conversion des valeurs du formulaire
    formData.forEach((val, key) => {
      key = key.replace(/\[\]$/, ""); // supprime les [] pour les checkbox multiples
      if (filters[key]) {
        filters[key] = [].concat(filters[key], val); // transforme en tableau
      } else {
        filters[key] = val;
      }
    });

    // Récupération des valeurs du slider directement
    this.container.querySelectorAll(".double-slider").forEach(slider => {
      const filterName = slider.dataset.filter;
      const min = parseInt(slider.dataset.valueLow);
      const max = parseInt(slider.dataset.valueHigh);
      filters[`${filterName}Min`] = min;
      filters[`${filterName}Max`] = max;
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

      // Injection des résultats
      if (this.resultsTarget) this.resultsTarget.innerHTML = data.results;

      // Pagination
      if (this.paginationTopTarget)
        this.paginationTopTarget.innerHTML = data.paginationTop;
      if (this.paginationBottomTarget)
        this.paginationBottomTarget.innerHTML = data.paginationBottom;

      // Réinitialisation sliders après injection AJAX
      this.initSliders();

      // Scroll vers résultats
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

// Initialisation après que le DOM soit chargé
document.addEventListener("DOMContentLoaded", () => {
  new VehiclesFilter();
});
