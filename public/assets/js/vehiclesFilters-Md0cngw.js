// VehiclesFilter.js
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(formSelector = "#filters-form") {
    // Sélection du formulaire
    this.form = document.querySelector(formSelector);
    if (!this.form) return;

    // Conteneur parent (utile pour delegation)
    this.container = this.form.closest("[data-fetch-form]");
    if (!this.container) return;

    // Targets pour injection AJAX
    this.resultsTarget = document.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = document.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = document.querySelector(
      "[data-target='pagination-bottom']"
    );

    this.fetchUrl = this.form.dataset.fetchUrl;
    this.debounceTimeout = null;

    // Initialisation des sliders et événements
    this.initSliders();
    this.bindEvents();
  }

  // Initialisation des sliders présents dans le formulaire
  initSliders() {
    this.form.querySelectorAll(".double-slider").forEach(slider => {
      // On initialise chaque slider une seule fois
      if (!slider.dataset.initialized) {
        initDoubleSlider(slider);
        slider.dataset.initialized = "true";
      }
    });
  }

  // Attachement des événements
  bindEvents() {
    // Delegation pour tous les inputs et selects dans le container
    this.container.addEventListener("change", e => {
      if (e.target.matches("input, select")) {
        this.debounceSubmit();
      }
    });

    // Soumission classique (au cas où)
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.debounceSubmit();
    });

    // Pagination click (delegation sur document)
    document.addEventListener("click", e => {
      const link = e.target.closest("a[data-page]");
      if (link) {
        e.preventDefault();
        const page = Number.parseInt(link.dataset.page);
        this.debounceSubmit(page);
      }
    });

    // Écoute globale des sliders
    document.addEventListener("sliderChanged", () => {
      this.debounceSubmit();
    });

    // Scroll vers les résultats après injection
    this.scrollToResults = () => {
      if (this.resultsTarget) {
        this.resultsTarget.scrollIntoView({
          behavior: "smooth",
          block: "start"
        });
      }
    };
  }

  // Debounce pour limiter les requêtes AJAX
  debounceSubmit(page = 1, delay = 150) {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(page), delay);
  }

  // Soumission AJAX des filtres
  async submitFilters(page = 1) {
    if (!this.fetchUrl) return;

    // Construction de l'objet filters depuis le formulaire
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

      // Réinitialisation des sliders sur le DOM actuel
      this.initSliders();

      // Scroll vers les résultats
      this.scrollToResults();
    } catch (e) {
      console.error("VehiclesFilter AJAX error", e);
    }
  }
}
