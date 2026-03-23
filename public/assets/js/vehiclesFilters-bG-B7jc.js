// VehiclesFilter.js
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(container = document.body) {
    if (!(container instanceof HTMLElement)) {
      console.warn(
        "[VehiclesFilter] container invalide, utilisation document.body"
      );
      container = document.body;
    }
    this.container = container;
    this.debounceTimeout = null;

    this.initForm();
  }

  initForm() {
    // Cherche le formulaire dans le container
    this.form = this.container.querySelector("[data-fetch-form]");
    if (!this.form) {
      console.warn("[VehiclesFilter] Aucun formulaire trouvé");
      return;
    }

    this.fetchUrl = this.form.dataset.fetchUrl;
    if (!this.fetchUrl) {
      console.warn("[VehiclesFilter] fetchUrl manquant sur le formulaire");
      return;
    }

    // Targets pour injection AJAX
    this.resultsTarget = this.container.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = this.container.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = this.container.querySelector(
      "[data-target='pagination-bottom']"
    );

    // Initialisation sliders
    this.container.querySelectorAll(".double-slider").forEach(slider => {
      initDoubleSlider(slider);
      slider.addEventListener("sliderChanged", () => this.debounceSubmit());
    });

    // Événements
    this.bindEvents();
  }

  bindEvents() {
    // Soumission formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.debounceSubmit();
    });

    // Changement sur inputs et selects (checkboxes incluses)
    this.container.addEventListener("change", e => {
      if (e.target.matches("input, select")) {
        this.debounceSubmit();
      }
    });

    // Pagination
    this.container.addEventListener("click", e => {
      const link = e.target.closest("[data-page]");
      if (link) {
        e.preventDefault();
        const page = parseInt(link.dataset.page);
        if (!isNaN(page)) this.debounceSubmit(page);
      }
    });
  }

  debounceSubmit(page = 1, delay = 150) {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(page), delay);
  }

  async submitFilters(page = 1) {
    if (!this.form || !this.fetchUrl) return;

    // Récupère les valeurs du formulaire
    const formData = new FormData(this.form);
    const filters = {};

    // Checkbox / select → tableau
    formData.forEach((val, key) => {
      const cleanKey = key.replace(/\[\]$/, "");
      if (filters[cleanKey])
        filters[cleanKey] = [].concat(filters[cleanKey], val);
      else filters[cleanKey] = [val];
    });

    // Sliders
    this.container.querySelectorAll(".double-slider").forEach(slider => {
      const filterName = slider.dataset.filter;
      filters[`${filterName}Min`] = parseInt(slider.dataset.valueLow);
      filters[`${filterName}Max`] = parseInt(slider.dataset.valueHigh);
    });

    // Envoi AJAX
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

      // Injection dans les targets
      if (this.resultsTarget) this.resultsTarget.innerHTML = data.results;
      if (this.paginationTopTarget)
        this.paginationTopTarget.innerHTML = data.paginationTop;
      if (this.paginationBottomTarget)
        this.paginationBottomTarget.innerHTML = data.paginationBottom;
    } catch (e) {
      console.error("[VehiclesFilter] AJAX error :", e);
    }
  }
}

// Initialisation automatique sur DOMContentLoaded
document.addEventListener("DOMContentLoaded", () => {
  new VehiclesFilter(document.body);
});

// Pour sidebar injectée dynamiquement, faire après injection :
// new VehiclesFilter(document.querySelector("#sidebar"));
