/**
 * VehiclesFilter.js
 * ------------------------------------------------------------------
 * VERSION STABLE CORRIGÉE
 *
 * OBJECTIF :
 * - UI uniquement
 * - aucune dépendance fragile au DOM global
 * - aucun fetch direct
 * - initialisation robuste même après ui:updated
 * ------------------------------------------------------------------
 */

import FilterBadges from "./FilterBadges.js";
import initDoubleSlider from "./rangeSelector.js";
import Autocomplete from "./Autocomplete.js";

export default class VehiclesFilter {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    /**
     * Anti double init (important après ui:updated)
     */
    if (form.dataset.vehiclesFilterInit === "1") return;
    form.dataset.vehiclesFilterInit = "1";

    this.form = form;

    /**
     * IMPORTANT FIX :
     * on n’utilise PLUS un sélecteur data-target inexistant
     * on se base uniquement sur le container réel
     */
    this.container = document.querySelector("#vehicles-results");

    /**
     * Alias interne stable (compatibilité ancienne logique)
     */
    this.resultsEl = this.container;

    this.paginationTop = document.querySelector(
      '[data-target="pagination-top"]'
    );
    this.paginationBottom = document.querySelector(
      '[data-target="pagination-bottom"]'
    );

    this.summaryContainer = document.querySelector(
      '[data-target="filters-summary"]'
    );

    /**
     * Sécurité DOM minimale
     */
    if (!this.container) {
      console.warn("VehiclesFilter: container #vehicles-results introuvable");
      return;
    }

    this.loading = false;

    /**
     * Badges filters (optionnel)
     */
    if (this.summaryContainer) {
      this.badges = new FilterBadges(this.summaryContainer, this.form, () =>
        this.triggerFetch()
      );
    }

    this.initSliders();
    this.initEvents();
    this.initAutocomplete();
    this.initCardsClick();
    this.initViewSwitcher();
  }

  /**
   * Trigger central SAFE
   * (ne dépend plus du DOM global)
   */
  triggerFetch() {
    if (!this.form) return;

    this.form.dispatchEvent(new Event("change", { bubbles: true }));
  }

  /**
   * Sliders double range
   */
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

  /**
   * Events filtres (scope LOCAL uniquement)
   */
  initEvents() {
    if (this.eventsBound) return;
    this.eventsBound = true;

    /**
     * CHANGE filters
     */
    this.form.addEventListener("change", e => {
      const el = e.target;
      if (!(el instanceof HTMLElement)) return;
      if (!el.matches("input, select")) return;

      this.triggerFetch();
    });

    /**
     * PAGINATION
     */
    document.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;

      e.preventDefault();

      const page = parseInt(btn.dataset.page, 10);
      if (!isNaN(page)) {
        const pageInput = this.form.querySelector("input[name='page']");
        if (pageInput) pageInput.value = page;

        this.triggerFetch();
      }
    });

    /**
     * BADGES REMOVE
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

        if (this.badges) {
          this.badges.updateBadges();
        }

        this.triggerFetch();
      });
    }
  }

  /**
   * Switch grid / table
   */
  initViewSwitcher() {
    const inputs = this.form.querySelectorAll("input[name='view']");

    inputs.forEach(input => {
      input.addEventListener("change", () => {
        this.triggerFetch();
      });
    });
  }

  /**
   * Autocomplete UI uniquement
   */
  initAutocomplete() {
    this.form.querySelectorAll("[data-autocomplete]").forEach(input => {
      if (input.dataset.autocompleteInitialized === "1") return;

      new Autocomplete(input);
      input.dataset.autocompleteInitialized = "1";
    });
  }

  /**
   * Click sur cards véhicules
   */
  initCardsClick() {
    if (!this.resultsEl) return;

    this.resultsEl.addEventListener("click", e => {
      const card = e.target.closest(".vehicle-item[data-url]");
      if (!card) return;

      const url = card.dataset.url;
      if (!url) return;

      window.location.href = url;
    });
  }
}
