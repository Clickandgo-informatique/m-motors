// VehiclesFilter.js
// Gestion des filtres véhicules (sidebar) avec AJAX, pagination et sliders

import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(formSelector = "#filters-form") {
    this.form = document.querySelector(formSelector);
    if (!this.form) return;

    // Container global avec data-fetch-form et data-fetch-url
    this.container = this.form.closest("[data-fetch-form]");
    if (!this.container) return;

    // Cibles d'injection
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
    if (!this.fetchUrl) {
      console.error(
        "VehiclesFilter : data-fetch-url manquant sur le container."
      );
      return;
    }

    // Initialisation sliders présents
    this.initSliders();

    // Debounce pour éviter les appels trop fréquents
    this.debounceTimeout = null;

    this.bindEvents();
  }

  bindEvents() {
    // Submit formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.submitFilters();
    });

    // Changement sur inputs/select
    this.form.querySelectorAll("input, select").forEach(el => {
      el.addEventListener("change", () => this.debounceSubmit());
    });

    // Pagination click (liens dynamiques)
    this.bindPaginationLinks();

    // Evénement global sliderChanged
    document.addEventListener("sliderChanged", e => {
      this.debounceSubmit();
    });
  }

  // Debounce pour éviter trop de requêtes
  debounceSubmit(delay = 250) {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(), delay);
  }

  initSliders() {
    const sliders = this.form.querySelectorAll(".double-slider");
    sliders.forEach(slider => initDoubleSlider(slider));
  }

  async submitFilters(page = 1) {
    if (!this.fetchUrl) return;

    const formData = new FormData(this.form);
    const filters = {};

    // Regroupe valeurs multiples en tableau
    for (const [key, value] of formData.entries()) {
      if (filters[key]) {
        if (!Array.isArray(filters[key])) filters[key] = [filters[key]];
        filters[key].push(value);
      } else {
        filters[key] = value;
      }
    }

    // Ajouter la page
    filters.page = page;

    try {
      const res = await fetch(this.fetchUrl, {
        method: "POST",
        body: JSON.stringify(filters),
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const data = await res.json();

      // Injection des résultats
      if (this.resultsTarget) this.resultsTarget.innerHTML = data.results || "";
      if (this.paginationTopTarget)
        this.paginationTopTarget.innerHTML = data.paginationTop || "";
      if (this.paginationBottomTarget)
        this.paginationBottomTarget.innerHTML = data.paginationBottom || "";

      // Re-binder les liens de pagination
      this.bindPaginationLinks();

      // Scroll top vers les résultats
      this.resultsTarget.scrollIntoView({ behavior: "smooth" });

      // Optionnel : mise à jour de l'URL sans recharger
      history.replaceState(null, "", `?page=${page}`);
    } catch (e) {
      console.error("VehiclesFilter AJAX error", e);
    }
  }

  bindPaginationLinks() {
    // Liens dynamiques de pagination
    const paginationLinks = this.container.querySelectorAll(".pagination-link");
    paginationLinks.forEach(link => {
      link.addEventListener("click", e => {
        e.preventDefault();
        const page = parseInt(link.dataset.page);
        if (page) this.submitFilters(page);
      });
    });
  }
}
