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

    this.onSliderChange = null; // pour détacher l’event global si besoin

    this.initSliders(); // initialisation des sliders
    this.bindEvents(); // bind des checkboxes, submit, pagination
  }

  /**
   * Initialisation des double sliders
   */
  initSliders() {
    const sliders = this.form.querySelectorAll(".double-slider");
    sliders.forEach(slider => {
      // ne pas réinitialiser un slider déjà initialisé
      if (!slider.dataset.initialized) {
        initDoubleSlider(slider);
        slider.dataset.initialized = "true";
      }
    });

    // écoute globale pour sliderChanged
    if (this.onSliderChange) {
      document.removeEventListener("sliderChanged", this.onSliderChange);
    }
    this.onSliderChange = e => this.submitFilters();
    document.addEventListener("sliderChanged", this.onSliderChange);
  }

  /**
   * Bind events sur formulaire, inputs et pagination
   */
  bindEvents() {
    // submit classique
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.submitFilters();
    });

    // changement sur checkbox / select
    this.form.querySelectorAll("input, select").forEach(el => {
      el.addEventListener("change", () => this.submitFilters());
    });

    // pagination click
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
   * Soumission des filtres via AJAX
   */
  async submitFilters(page = 1) {
    if (!this.fetchUrl) return;

    const formData = new FormData(this.form);
    const filters = {};

    // récupère les checkbox / select
    formData.forEach((val, key) => {
      if (filters[key]) filters[key] = [].concat(filters[key], val);
      else filters[key] = val;
    });

    // récupère aussi les sliders
    this.form.querySelectorAll(".double-slider").forEach(slider => {
      const filter = slider.dataset.filter;
      const min = Number(slider.dataset.valueLow);
      const max = Number(slider.dataset.valueHigh);
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

      if (this.resultsTarget) this.resultsTarget.innerHTML = data.results;
      if (this.paginationTopTarget)
        this.paginationTopTarget.innerHTML = data.paginationTop;
      if (this.paginationBottomTarget)
        this.paginationBottomTarget.innerHTML = data.paginationBottom;

      // réinitialisation des sliders seulement si nouveau DOM injecté
      this.initSliders();
    } catch (e) {
      console.error("VehiclesFilter AJAX error", e);
    }
  }
}
