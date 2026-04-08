import FilterBadges from "./FilterBadges.js";
import initDoubleSlider from "./rangeSelector.js";
import Autocomplete from "./Autocomplete.js";

export default class VehiclesFilter {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;
    this.form = form;
    this.url = form.dataset.fetchUrl;
    if (!this.url) return;

    this.container = document.querySelector("#vehicles-container");
    this.resultsEl = this.container?.querySelector(
      '[data-target="vehicles-search-results"]'
    );
    this.paginationTop = document.querySelector(
      '[data-target="pagination-top"]'
    );
    this.paginationBottom = document.querySelector(
      '[data-target="pagination-bottom"]'
    );
    this.summaryContainer = document.querySelector(
      '[data-target="filters-summary"]'
    );

    if (!this.container || !this.resultsEl) {
      console.warn(
        "VehiclesFilter : container de résultats introuvable",
        this.container
      );
      return;
    }

    if (this.summaryContainer && this.form.matches("#filters-form")) {
      this.badges = new FilterBadges(
        this.summaryContainer,
        this.form,
        this.submitFilters.bind(this)
      );
    }

    if (this.form.matches("#filters-form")) this.initSliders();
    this.initEvents();
    this.initAutocomplete();
  }

  initSliders() {
    /* identique à ton code précédent */
  }
  initEvents() {
    /* identique à ton code précédent */
  }

  async submitFilters(page = 1) {
    try {
      const formData = new FormData(this.form);
      const filters = {};
      for (const [key, value] of formData.entries()) {
        const match = key.match(/^filters\[(.+?)\](\[\])?$/);
        if (!match) continue;
        const name = match[1];
        const isArray = !!match[2];
        if (isArray) {
          filters[name] = filters[name] || [];
          filters[name].push(value);
        } else filters[name] = value;
      }

      const viewInput = this.form.querySelector("input[name='view']:checked");
      if (viewInput) filters.view = viewInput.value;

      const res = await fetch(this.url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters, page })
      });

      const data = await res.json();

      if (this.container && data.results)
        this.resultsEl.innerHTML = data.results;
      if (this.paginationTop && data.paginationTop)
        this.paginationTop.innerHTML = data.paginationTop;
      if (this.paginationBottom && data.paginationBottom)
        this.paginationBottom.innerHTML = data.paginationBottom;
      if (this.badges) this.badges.updateBadges();
      this.initAutocomplete();
    } catch (err) {
      console.error("Erreur AJAX :", err);
    }
  }

  initAutocomplete() {
    this.form.querySelectorAll("[data-autocomplete]").forEach(input => {
      if (!input.dataset.autocompleteInitialized) {
        new Autocomplete(input);
        input.dataset.autocompleteInitialized = "true";
      }
    });
  }
}
