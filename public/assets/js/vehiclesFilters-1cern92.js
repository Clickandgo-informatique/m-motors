// VehiclesFilter.js
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(container = document.body) {
    // Container du formulaire
    this.container = container;

    // Cherche le formulaire
    this.form = this.container.querySelector("[data-fetch-form]");
    console.log("[VehiclesFilter] Form détecté :", this.form);

    if (!this.form) return;

    this.fetchUrl = this.form.dataset.fetchUrl;
    if (!this.fetchUrl) {
      console.warn("[VehiclesFilter] Aucun fetchUrl trouvé");
      return;
    }

    // Targets pour injection
    this.resultsTarget = this.container.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = this.container.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = this.container.querySelector(
      "[data-target='pagination-bottom']"
    );

    this.debounceTimeout = null;

    // Initialisation sliders et events
    this.initSliders();
    this.bindEvents();
  }

  initSliders() {
    this.container.querySelectorAll(".double-slider").forEach(slider => {
      initDoubleSlider(slider);

      slider.addEventListener("sliderChanged", e => {
        // Stocke les valeurs dans dataset pour les récupérer lors de la soumission
        slider.dataset.valueLow = e.detail.min;
        slider.dataset.valueHigh = e.detail.max;
        console.log("[VehiclesFilter] SliderChanged :", e.detail);
        this.debounceSubmit();
      });
    });
  }

  bindEvents() {
    // Soumission formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.debounceSubmit();
    });

    // Changement sur input / select
    this.container.addEventListener("change", e => {
      if (e.target.matches("input, select")) {
        console.log(
          "[VehiclesFilter] Input/Select changé :",
          e.target.name,
          e.target.value
        );
        this.debounceSubmit();
      }
    });

    // Pagination
    this.container.addEventListener("click", e => {
      const link = e.target.closest("[data-page]");
      if (link) {
        e.preventDefault();
        const page = parseInt(link.dataset.page);
        if (!isNaN(page)) {
          console.log("[VehiclesFilter] Pagination click page :", page);
          this.debounceSubmit(page);
        }
      }
    });
  }

  debounceSubmit(page = 1, delay = 200) {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(page), delay);
  }

  async submitFilters(page = 1) {
    const formData = new FormData(this.form);
    const filters = {};

    // Checkbox / select → toujours en tableau
    formData.forEach((val, key) => {
      const cleanKey = key.replace(/\[\]$/, "");
      if (filters[cleanKey]) {
        filters[cleanKey] = [].concat(filters[cleanKey], val);
      } else {
        filters[cleanKey] = [val];
      }
    });

    // Sliders
    this.container.querySelectorAll(".double-slider").forEach(slider => {
      const filterName = slider.dataset.filter;
      filters[`${filterName}Min`] = parseInt(slider.dataset.valueLow);
      filters[`${filterName}Max`] = parseInt(slider.dataset.valueHigh);
    });

    console.log("[VehiclesFilter] Filters envoyés :", filters);

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
      console.log("[VehiclesFilter] Réponse AJAX :", data);

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

// Initialisation DOMContentLoaded
document.addEventListener("DOMContentLoaded", () => {
  console.log("[VehiclesFilter] Initialisation DOMContentLoaded");
  new VehiclesFilter(document.body);
});
