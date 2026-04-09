// assets/js/vehiclesFilters.js
import FilterBadges from "./FilterBadges.js";
import initDoubleSlider from "./rangeSelector.js";
import Autocomplete from "./Autocomplete.js";

/**
 * Classe principale pour la gestion des filtres véhicules
 */
export default class VehiclesFilter {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.url = form.dataset.fetchUrl;

    if (!this.url) return;

    // Conteneurs principaux
    this.container =
      document.querySelector("#vehicles-results") ||
      document.querySelector("#vehicles-container");
    this.resultsEl = this.container?.querySelector(
      '[data-target="vehicles-search-results"]'
    );
    this.paginationTop = this.container?.querySelector(
      '[data-target="pagination-top"]'
    );
    this.paginationBottom = this.container?.querySelector(
      '[data-target="pagination-bottom"]'
    );
    this.summaryContainer = this.container?.querySelector(
      '[data-target="filters-summary"]'
    );

    if (!this.container || !this.resultsEl) {
      console.warn(
        "VehiclesFilter : container de résultats introuvable",
        this.container
      );
      return;
    }

    // INIT BADGES
    if (this.summaryContainer && this.form.matches("#filters-form")) {
      this.badges = new FilterBadges(
        this.summaryContainer,
        this.form,
        this.submitFilters.bind(this)
      );
    }

    // INIT SLIDERS
    if (this.form.matches("#filters-form")) this.initSliders();

    // INIT EVENTS
    this.initEvents();

    // INIT AUTOCOMPLETE
    this.initAutocomplete();

    // INIT CARDS CLICK
    this.initCardsClick();
  }

  initSliders() {
    const sliders = this.form.querySelectorAll(".double-slider");
    if (!sliders.length || typeof initDoubleSlider !== "function") return;

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
        timer = setTimeout(() => this.submitFilters(), 300);
      });
    });
  }

  initEvents() {
    // Changement sur filtres
    this.form.addEventListener("change", e => {
      if (!e.target.matches("input, select")) return;
      this.submitFilters();
    });

    // Pagination
    this.container.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;
      e.preventDefault();
      const page = Number.parseInt(btn.dataset.page);
      if (!isNaN(page)) this.submitFilters(page);
    });

    // Suppression badges
    if (this.badges) {
      this.summaryContainer.addEventListener("click", e => {
        if (!e.target.matches(".badge-remove")) return;

        const filter = e.target.dataset.filter;
        const value = e.target.dataset.value;

        // Reset slider si applicable
        const slider = this.form.querySelector(
          `.double-slider[data-filter="${filter}"]`
        );
        if (slider && typeof slider.resetSlider === "function")
          slider.resetSlider();
        else {
          const checkboxes = this.form.querySelectorAll(
            `input[name="filters[${filter}][]"]`
          );
          checkboxes.forEach(cb => {
            if (cb.value === value) cb.checked = false;
          });
        }

        this.badges.updateBadges();
        this.submitFilters();
      });
    }
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
          if (!filters[name]) filters[name] = [];
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

      if (this.resultsEl) {
        this.resultsEl.innerHTML =
          data.results && data.results.trim() !== ""
            ? data.results
            : "<div class='text-center text-muted'>Aucun véhicule trouvé</div>";
      }

      if (this.paginationTop && data.paginationTop)
        this.paginationTop.innerHTML = data.paginationTop;
      if (this.paginationBottom && data.paginationBottom)
        this.paginationBottom.innerHTML = data.paginationBottom;

      if (this.badges) this.badges.updateBadges();

      // Re-init autocomplete
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

  initCardsClick() {
    // Delegation sur resultsEl ou document
    const container = this.resultsEl || document;

    container.addEventListener("click", e => {
      const card = e.target.closest(".vehicle-card[data-item-link]");
      if (!card) return;

      const url = card.dataset.itemLink;
      if (!url) return;

      console.log("Ouverture modal AJAX pour :", url);

      if (
        window.AjaxManagerInstance &&
        typeof window.AjaxManagerInstance.loadModal === "function"
      ) {
        window.AjaxManagerInstance.loadModal(url);
      } else {
        console.warn("AjaxManagerInstance non trouvé, fallback redirection");
        window.location.href = url;
      }
    });
  }
}

/**
 * Observer global pour init automatique sur tous les formulaires fetch
 */
function watchFetchForms() {
  const observer = new MutationObserver(() => {
    document.querySelectorAll("[data-fetch-form]").forEach(form => {
      if (form.dataset.initialized) return;
      form.dataset.initialized = "true";
      new VehiclesFilter(form);
    });
  });

  observer.observe(document.body, { childList: true, subtree: true });
}

document.addEventListener("DOMContentLoaded", () => {
  watchFetchForms();
});
