import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(container) {
    // Vérifie que container est un HTMLElement
    if (!(container instanceof HTMLElement)) {
      console.warn(
        "[VehiclesFilter] container invalide, utilisation document.body"
      );
      container = document.body;
    }

    this.container = container;

    // Cherche le formulaire
    this.form = this.container.querySelector("[data-fetch-form]");
    if (!this.form) {
      console.warn(
        "[VehiclesFilter] Aucun formulaire trouvé dans le container",
        this.container
      );
      return;
    }

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

    this.initSliders();
    this.bindEvents();
  }

  initSliders() {
    this.container.querySelectorAll(".double-slider").forEach(slider => {
      initDoubleSlider(slider);
      slider.addEventListener("sliderChanged", e => {
        slider.dataset.valueLow = e.detail.min;
        slider.dataset.valueHigh = e.detail.max;
        this.debounceSubmit();
      });
    });
  }

  bindEvents() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.debounceSubmit();
    });

    this.container.addEventListener("change", e => {
      if (e.target.matches("input, select")) {
        this.debounceSubmit();
      }
    });

    this.container.addEventListener("click", e => {
      const link = e.target.closest("[data-page]");
      if (link) {
        e.preventDefault();
        const page = parseInt(link.dataset.page);
        if (!isNaN(page)) this.debounceSubmit(page);
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

    formData.forEach((val, key) => {
      const cleanKey = key.replace(/\[\]$/, "");
      if (filters[cleanKey]) {
        filters[cleanKey] = [].concat(filters[cleanKey], val);
      } else {
        filters[cleanKey] = [val];
      }
    });

    this.container.querySelectorAll(".double-slider").forEach(slider => {
      const filterName = slider.dataset.filter;
      filters[`${filterName}Min`] = parseInt(slider.dataset.valueLow);
      filters[`${filterName}Max`] = parseInt(slider.dataset.valueHigh);
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

// Initialisation après DOM ready
document.addEventListener("DOMContentLoaded", () => {
  new VehiclesFilter(document.body);
});
