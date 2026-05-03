/**
 * VehiclesFilter.js
 * ------------------------------------------------------------------
 * VERSION STABLE (UI ONLY)
 *
 * FIXES :
 * - correction typo input Max
 * - suppression listeners globaux dangereux
 * - triggerFetch sécurisé
 * - plus de dépendance DOM global
 * ------------------------------------------------------------------
 */

import FilterBadges from "./FilterBadges.js";
import initDoubleSlider from "./rangeSelector.js";
import Autocomplete from "./Autocomplete.js";

export default class VehiclesFilter {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    if (form.dataset.vehiclesFilterInit === "1") return;
    form.dataset.vehiclesFilterInit = "1";

    this.form = form;

    this.container = document.querySelector("#vehicles-results");
    this.resultsEl = document.querySelector(
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
      console.warn("VehiclesFilter: DOM incomplet");
      return;
    }

    if (this.summaryContainer) {
      this.badges = new FilterBadges(this.summaryContainer, this.form, () =>
        this.triggerFetch()
      );
    }

    this.initSliders();
    this.initViewSwitcher();
    this.initCardsClick();
    this.initAutocomplete();
  }

  /**
   * SAFE trigger (ne dépend PAS du DOM global)
   */
  triggerFetch() {
    const event = new Event("change", { bubbles: true });
    this.form.dispatchEvent(event);
  }

  initSliders() {
    const sliders = this.form.querySelectorAll(".double-slider");
    if (!sliders.length) return;

    sliders.forEach(slider => {
      initDoubleSlider(slider);

      let timer = null;

      slider.addEventListener("sliderChanged", e => {
        const { filter, min, max } = e.detail;

        const inputMin = this.form.querySelector(
          `input[name="filters[${filter}Min]"]`
        );

        const inputMax = this.form.querySelector(
          `input[name="filters[${filter}Max]"]` // FIX TYPO
        );

        if (inputMin) inputMin.value = min;
        if (inputMax) inputMax.value = max;

        clearTimeout(timer);
        timer = setTimeout(() => this.triggerFetch(), 250);
      });
    });
  }

  initViewSwitcher() {
    const inputs = this.form.querySelectorAll("input[name='view']");

    inputs.forEach(input => {
      input.addEventListener("change", () => {
        this.triggerFetch();
      });
    });
  }

  initAutocomplete() {
    this.form.querySelectorAll("[data-autocomplete]").forEach(input => {
      if (input.dataset.autocompleteInitialized === "1") return;

      new Autocomplete(input);
      input.dataset.autocompleteInitialized = "1";
    });
  }

  initCardsClick() {
    if (!this.resultsEl) return;

    this.resultsEl.addEventListener("click", e => {
      const card = e.target.closest(".vehicle-item[data-url]");
      if (!card) return;

      const url = card.dataset.url;
      if (url) window.location.href = url;
    });
  }
}
