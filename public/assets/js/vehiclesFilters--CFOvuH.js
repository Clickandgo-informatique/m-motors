// VehiclesFilter.js
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(formSelector = "#filters-form") {
    this.form = document.querySelector(formSelector);
    if (!this.form) return;

    this.container = this.form.closest("[data-fetch-form]");
    if (!this.container) return;

    this.resultsTarget = this.container.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = this.container.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = this.container.querySelector(
      "[data-target='pagination-bottom']"
    );
    this.fetchUrl = this.container.dataset.fetchUrl;

    this.debounceTimeout = null;

    this.initSliders();
    this.bindEvents();
  }

  /**
   * Initialise tous les sliders double
   */
  initSliders() {
    this.form.querySelectorAll(".double-slider").forEach(slider => {
      if (!slider.dataset.initialized) {
        initDoubleSlider(slider);

        // Mettre à jour dataset pour récupérer valeurs externes
        slider.dataset.initialized = "true";
        slider.addEventListener("sliderChanged", e => {
          this.debounceSubmit();
        });
      }
    });
  }

  /**
   * Debounce pour éviter requêtes multiples
   */
  debounceSubmit(delay = 300) {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(), delay);
  }

  /**
   * Liaison des événements
   */
  bindEvents() {
    // Envoi form
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.submitFilters();
    });

    // Sur changement de champ
    this.form.querySelectorAll("input, select").forEach(el => {
      el.addEventListener("change", () => this.debounceSubmit());
    });

    // Pagination click
    this.container.addEventListener("click", e => {
      const link = e.target.closest(".pagination-link");
      if (link) {
        e.preventDefault();
        const page = link.dataset.page;
        this.submitFilters(page);
      }
    });
  }

  /**
   * Envoi AJAX avec mise à jour résultats + pagination + URL
   */
  async submitFilters(page = 1) {
    if (!this.fetchUrl) return;

    const formData = new FormData(this.form);
    const filters = {};

    // Transforme FormData en objet
    formData.forEach((val, key) => {
      if (filters[key]) filters[key] = [].concat(filters[key], val);
      else filters[key] = val;
    });

    // Récupération des sliders
    this.form.querySelectorAll(".double-slider").forEach(slider => {
      const filter = slider.dataset.filter;
      const min = Number(slider.dataset.valueLow ?? slider.dataset.min);
      const max = Number(slider.dataset.valueHigh ?? slider.dataset.max);
      filters[filter] = { min, max };
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

      // Injection HTML
      if (this.resultsTarget) this.resultsTarget.innerHTML = data.results;
      if (this.paginationTopTarget)
        this.paginationTopTarget.innerHTML = data.paginationTop;
      if (this.paginationBottomTarget)
        this.paginationBottomTarget.innerHTML = data.paginationBottom;

      // Réinitialisation sliders (si nouveau DOM)
      this.initSliders();

      // Mise à jour de l'URL sans recharger
      const params = new URLSearchParams();
      Object.keys(filters).forEach(key => {
        if (typeof filters[key] === "object") {
          params.set(key + "_min", filters[key].min);
          params.set(key + "_max", filters[key].max);
        } else if (Array.isArray(filters[key])) {
          filters[key].forEach(val => params.append(key + "[]", val));
        } else {
          params.set(key, filters[key]);
        }
      });
      params.set("page", page);
      const newUrl = `${window.location.pathname}?${params.toString()}`;
      window.history.pushState({ path: newUrl }, "", newUrl);
    } catch (e) {
      console.error("VehiclesFilter AJAX error", e);
    }
  }
}
