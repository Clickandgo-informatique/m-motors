// VehiclesFilter.js
// Gestion des filtres AJAX pour les véhicules (checkboxes, sliders, pagination)
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(formSelector = "#filters-form") {
    // Sélection du formulaire
    this.form = document.querySelector(formSelector);
    if (!this.form) return;

    // Container parent pour delegation et injection
    this.container = this.form.closest("[data-fetch-form]") || document;
    this.fetchUrl = this.form.dataset.fetchUrl; // URL AJAX
    this.debounceTimeout = null;

    // Targets dynamiques pour injection AJAX
    this.resultsTarget = document.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = document.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = document.querySelector(
      "[data-target='pagination-bottom']"
    );

    // Initialisation
    this.initSliders();
    this.bindEvents();
  }

  // Initialise tous les sliders du formulaire
  initSliders() {
    this.form.querySelectorAll(".double-slider").forEach(slider => {
      initDoubleSlider(slider);

      // Slider change → déclenche le filtre via debounce
      slider.addEventListener("sliderChanged", () => this.debounceSubmit());
    });
  }

  // Liaison des événements
  bindEvents() {
    // Soumission manuelle du formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.debounceSubmit();
    });

    // Changement sur tous les inputs/selects via delegation
    this.container.addEventListener("change", e => {
      if (e.target.matches("input, select")) {
        this.debounceSubmit();
      }
    });

    // Pagination (boutons "Précédent", "Suivant", ou rangée)
    this.container.addEventListener("click", e => {
      const link = e.target.closest("a[data-page], button[data-page]");
      if (link) {
        e.preventDefault();
        const page = Number.parseInt(link.dataset.page);
        this.debounceSubmit(page);
      }
    });

    // Scroll vers les résultats après injection AJAX
    this.scrollToResults = () => {
      if (this.resultsTarget) {
        this.resultsTarget.scrollIntoView({
          behavior: "smooth",
          block: "start"
        });
      }
    };
  }

  // Debounce pour limiter les requêtes AJAX rapides
  debounceSubmit(page = 1, delay = 150) {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(page), delay);
  }

  // Récupération des valeurs du formulaire et envoi AJAX
  async submitFilters(page = 1) {
    if (!this.fetchUrl) return;

    const formData = new FormData(this.form);
    const filters = {};

    // Conversion FormData → objet filters
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

      // Injection des fragments HTML
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
