/**
 * VehiclesFilter.js
 * ------------------------------------------------------------------
 * VERSION STABILISÉE
 *
 * Rôle :
 * - UI uniquement (sliders, badges, interactions)
 * - délègue le fetch à FetchForm
 * - évite absolument les multi-listeners (cause des 60 requêtes)
 * ------------------------------------------------------------------
 */

import FilterBadges from "./FilterBadges.js";
import initDoubleSlider from "./rangeSelector.js";
import Autocomplete from "./Autocomplete.js";

export default class VehiclesFilter {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    // Protection anti double initialisation (CRITIQUE)
    if (form.dataset.vehiclesFilterBound === "1") return;
    form.dataset.vehiclesFilterBound = "1";

    this.form = form;

    this.container = document.querySelector("#vehicles-results");
    this.resultsEl = document.querySelector("#vehicles-results");

    this.paginationTop = document.querySelector(
      '[data-target="pagination-top"]'
    );
    this.paginationBottom = document.querySelector(
      '[data-target="pagination-bottom"]'
    );

    this.summaryContainer = document.querySelector(
      '[data-target="filters-summary"]'
    );

    if (!this.container) {
      console.warn("VehiclesFilter: DOM incomplet");
      return;
    }

    this.initSliders();
    this.initEvents();
    this.initAutocomplete();
    this.initCardsClick();
  }

  /**
   * Déclenchement unique du fetch via FetchForm
   */
  triggerFetch() {
    this.form.dispatchEvent(new Event("change", { bubbles: true }));
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
          `input[name="filters[${filter}Max]"]`
        );

        if (inputMin) inputMin.value = min;
        if (inputMax) inputMax.value = max;

        clearTimeout(timer);
        timer = setTimeout(() => this.triggerFetch(), 250);
      });
    });
  }

  initEvents() {
    if (this.eventsBound) return;
    this.eventsBound = true;

    /**
     * FILTRES (uniquement dans le form)
     */
    this.form.addEventListener("change", e => {
      const el = e.target;
      if (!(el instanceof HTMLElement)) return;

      if (!el.matches("input, select")) return;

      this.triggerFetch();
    });

    /**
     * PAGINATION (CRITIQUE FIX ANTI SPAM REQUEST)
     */
    this.form.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;

      e.preventDefault();
      e.stopPropagation();

      const page = parseInt(btn.dataset.page, 10);
      if (isNaN(page)) return;

      const pageInput = this.form.querySelector("input[name='page']");
      if (pageInput) {
        pageInput.value = page;
      }

      this.triggerFetch();
    });

    /**
     * BADGES
     */
    if (this.summaryContainer) {
      this.summaryContainer.addEventListener("click", e => {
        const btn = e.target.closest(".badge-remove");
        if (!btn) return;

        const filter = btn.dataset.filter;
        const value = btn.dataset.value;

        const checkboxes = this.form.querySelectorAll(
          `input[name="filters[${filter}][]"]`
        );

        checkboxes.forEach(cb => {
          if (cb.value === value) cb.checked = false;
        });

        this.triggerFetch();
      });
    }
  }

  initAutocomplete() {
    this.form.querySelectorAll("[data-autocomplete]").forEach(input => {
      if (input.dataset.autocompleteInitialized === "1") return;

      new Autocomplete(input);
      input.dataset.autocompleteInitialized = "1";
    });
  }

  initCardsClick() {
    const container = this.resultsEl || document;

    container.addEventListener("click", e => {
      const card = e.target.closest(".vehicle-item[data-url]");
      if (!card) return;

      const url = card.dataset.url;
      if (!url) return;

      window.location.href = url;
    });
  }
}
