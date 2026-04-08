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
    this.url = form.dataset.fetchUrl; // URL AJAX côté controller
    if (!this.url) return;

    // Conteneurs principaux
    this.container = document.querySelector("#vehicles-container");
    this.paginationTop = document.querySelector(
      '[data-target="pagination-top"]'
    );
    this.paginationBottom = document.querySelector(
      '[data-target="pagination-bottom"]'
    );
    this.summaryContainer = document.querySelector(
      '[data-target="filters-summary"]'
    );

    // --- INIT BADGES (si présent) ---
    if (this.summaryContainer && this.form.matches("#filters-form")) {
      this.badges = new FilterBadges(
        this.summaryContainer,
        this.form,
        this.submitFilters.bind(this)
      );
    }

    // --- INIT SLIDERS ---
    if (this.form.matches("#filters-form")) this.initSliders();

    // --- INIT EVENTS ---
    this.initEvents();

    // --- INIT AUTOCOMPLETE sur le formulaire (si présent) ---
    this.initAutocomplete();
  }

  /**
   * Initialisation des sliders double
   */
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

  /**
   * Initialisation des événements
   */
  initEvents() {
    // Changement sur filtres ou toggle view
    this.form.addEventListener("change", e => {
      if (!e.target.matches("input, select")) return;
      this.submitFilters();
    });

    // Pagination
    document.addEventListener("click", e => {
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
        if (slider && typeof slider.resetSlider === "function") {
          slider.resetSlider();
        } else {
          // Reset checkbox
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

  /**
   * Soumission AJAX du formulaire
   */
  async submitFilters(page = 1) {
    const formData = new FormData(this.form);
    const filters = {};

    // --- Récupération des filtres ---
    for (const [key, value] of formData.entries()) {
      const match = key.match(/^filters\[(.+?)\](\[\])?$/);
      if (!match) continue;
      const name = match[1];
      const isArray = !!match[2];

      if (isArray) {
        if (!filters[name]) filters[name] = [];
        filters[name].push(value);
      } else {
        filters[name] = value;
      }
    }

    // --- Ajout de la vue (grid/table) ---
    const viewInput = this.form.querySelector("input[name='view']:checked");
    if (viewInput) filters.view = viewInput.value;

    try {
      const res = await fetch(this.url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters, page })
      });

      const data = await res.json();

      // --- Injection des résultats dans le container dynamique ---
      const cardsContainerId =
        this.form.dataset.cardsContainer || "vehicles-search-results";
      const resultsEl = document.getElementById(cardsContainerId);
      if (resultsEl && data.results) {
        resultsEl.innerHTML = data.results;
      } else {
        console.warn(
          "VehiclesFilter : container de résultats introuvable",
          resultsEl
        );
      }

      // --- Pagination ---
      if (this.paginationTop && data.paginationTop)
        this.paginationTop.innerHTML = data.paginationTop;
      if (this.paginationBottom && data.paginationBottom)
        this.paginationBottom.innerHTML = data.paginationBottom;

      // --- Badges ---
      if (this.badges) this.badges.updateBadges();

      // --- Ré-init Autocomplete si nécessaire ---
      this.initAutocomplete();
    } catch (err) {
      console.error("Erreur AJAX :", err);
    }
  }

  /**
   * Initialisation autocomplete sur tous les inputs du formulaire
   */
  initAutocomplete() {
    this.form.querySelectorAll("[data-autocomplete]").forEach(input => {
      if (!input.dataset.autocompleteInitialized) {
        new Autocomplete(input);
        input.dataset.autocompleteInitialized = "true";
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

document.addEventListener("DOMContentLoaded", watchFetchForms);
