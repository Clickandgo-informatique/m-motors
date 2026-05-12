import initDoubleSlider from "./rangeSelector.js";
import Autocomplete from "./Autocomplete.js";

export default class VehiclesFilters {
  constructor(form, store) {
    this.form = form;
    this.store = store;

    this.resultsEl = document.querySelector("#vehicles-results");
    this.summaryContainer = document.querySelector(
      '[data-target="filters-summary"]'
    );

    this.bindEvents();
    this.initSliders();
    
    this.initAutocomplete();
    this.bindCardClick();
  }

  bindEvents() {
    // Inputs classiques
    this.form.addEventListener("change", e => {
      const el = e.target;
      if (!(el instanceof HTMLElement)) return;

      if (!el.matches("input, select")) return;

      this.syncFormToState();
    });

    // Pagination
    this.form.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;

      this.store.update({
        page: parseInt(btn.dataset.page, 10)
      });
    });

    // Badges
    if (this.summaryContainer) {
      this.summaryContainer.addEventListener("click", e => {
        const btn = e.target.closest(".badge-remove");
        if (!btn) return;

        const filter = btn.dataset.filter;
        const value = btn.dataset.value;

        const state = this.store.getState();

        const current = state.filters[filter] || [];

        const updated = current.filter(v => v !== value);

        this.store.update({
          filters: {
            [filter]: updated
          }
        });
      });
    }
  }

  /**
   * Synchronise le form vers le store
   */
  syncFormToState() {
    const formData = new FormData(this.form);

    const filters = {};

    for (const [key, value] of formData.entries()) {
      const match = key.match(/^filters\[(.+)\](\[\])?$/);

      if (match) {
        const name = match[1];

        if (!filters[name]) {
          filters[name] = [];
        }

        filters[name].push(value);
      }
    }

    this.store.update({
      filters
    });
  }

  initSliders() {
    const sliders = this.form.querySelectorAll(".double-slider");

    sliders.forEach(slider => {
      initDoubleSlider(slider);

      slider.addEventListener("sliderChanged", e => {
        const { filter, min, max } = e.detail;

        this.store.update({
          filters: {
            [`${filter}Min`]: min,
            [`${filter}Max`]: max
          }
        });
      });
    });
  }

  initAutocomplete() {
    this.form.querySelectorAll("[data-autocomplete]").forEach(input => {
      new Autocomplete(input);
    });
  }

  //Ouvre le formulaire d'un véhicule au clic sur une card
  bindCardClick() {
    document.addEventListener("click", e => {
      const card = e.target.closest(".vehicle-item[data-url]");
      if (!card) return;

      const url = card.dataset.url;
      if (!url) return;

      window.location.href = url;
    });
  }
}
