// VehiclesFilter.js

import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(formSelector = "#filters-form") {
    // Formulaire principal
    this.form = document.querySelector(formSelector);
    if (!this.form) {
      console.warn("VehiclesFilter : formulaire introuvable");
      return;
    }

    // Conteneur global (pour le résumé et pagination)
    this.container =
      this.form.closest("[data-fetch-form]") || this.form.parentElement;
    if (!this.container) {
      console.warn("VehiclesFilter : conteneur introuvable");
    }

    // Targets dynamiques
    this.resultsTarget = this.container.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = this.container.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = this.container.querySelector(
      "[data-target='pagination-bottom']"
    );
    this.filtersSummaryTarget = this.container.querySelector(
      "[data-target='filters-summary']"
    );

    this.fetchUrl = this.form.dataset.fetchUrl;
    this.debounceTimeout = null;

    // Initialisation
    this.initSliders();
    this.bindEvents();
    this.updateFiltersSummary(); // affichage initial du résumé
  }

  // Initialisation des sliders présents dans le formulaire
  initSliders() {
    this.form.querySelectorAll(".double-slider").forEach(slider => {
      initDoubleSlider(slider);

      // Écoute l'événement custom du slider
      slider.addEventListener("sliderChanged", e => {
        // Mise à jour des inputs cachés correspondant au slider
        const filter = slider.dataset.filter;
        const hiddenMin = this.form.querySelector(
          `input[name="filters[${filter}Min]"]`
        );
        const hiddenMax = this.form.querySelector(
          `input[name="filters[${filter}Max]"]`
        );
        if (hiddenMin) hiddenMin.value = e.detail.min;
        if (hiddenMax) hiddenMax.value = e.detail.max;

        this.debounceSubmit(); // soumission après modification
      });
    });
  }

  // Liaisons des événements
  bindEvents() {
    // Soumission manuelle du formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.debounceSubmit();
    });

    // Changement sur inputs / select
    this.form.querySelectorAll("input, select").forEach(el => {
      el.addEventListener("change", () => this.debounceSubmit());
    });

    // Pagination AJAX
    document.addEventListener("click", e => {
      const link = e.target.closest(".pagination a[data-page]");
      if (link) {
        e.preventDefault();
        const page = Number.parseInt(link.dataset.page);
        this.debounceSubmit(page);
      }
    });
  }

  // Déclenchement différé pour éviter les multiples requêtes rapides
  debounceSubmit(page = 1, delay = 150) {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(page), delay);
  }

  // Soumission AJAX des filtres
  async submitFilters(page = 1) {
    if (!this.fetchUrl) return;

    // Récupération des valeurs du formulaire
    const formData = new FormData(this.form);
    const filters = {};
    formData.forEach((val, key) => {
      if (filters[key]) filters[key] = [].concat(filters[key], val);
      else filters[key] = val;
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

      // Injection des résultats
      if (this.resultsTarget) this.resultsTarget.innerHTML = data.results;
      if (this.paginationTopTarget)
        this.paginationTopTarget.innerHTML = data.paginationTop;
      if (this.paginationBottomTarget)
        this.paginationBottomTarget.innerHTML = data.paginationBottom;

      // Mise à jour du résumé des filtres
      this.updateFiltersSummary();
    } catch (e) {
      console.error("VehiclesFilter AJAX error", e);
    }
  }

  // Génération du résumé des filtres actifs
  updateFiltersSummary() {
    if (!this.filtersSummaryTarget) return;

    const formData = new FormData(this.form);
    const activeFilters = [];

    for (let [key, value] of formData.entries()) {
      if (!value || value === "") continue;

      // Utiliser les labels pour des valeurs lisibles
      let labelText = value;

      if (key.includes("brand")) {
        const label = this.form.querySelector(
          `input[name="${key}"][value="${value}"] + label`
        );
        if (label) labelText = label.textContent;
        activeFilters.push(`Marque: ${labelText}`);
      } else if (key.includes("bodyType")) {
        const label = this.form.querySelector(
          `input[name="${key}"][value="${value}"] + label`
        );
        if (label) labelText = label.textContent;
        activeFilters.push(`Carrosserie: ${labelText}`);
      } else if (key.includes("fuelType")) {
        const label = this.form.querySelector(
          `input[name="${key}"][value="${value}"] + label`
        );
        if (label) labelText = label.textContent;
        activeFilters.push(`Carburant: ${labelText}`);
      } else if (key.includes("mileageMin")) {
        activeFilters.push(`Kilométrage min: ${value}`);
      } else if (key.includes("mileageMax")) {
        activeFilters.push(`Kilométrage max: ${value}`);
      } else if (key.includes("priceMin")) {
        activeFilters.push(`Prix min: ${value}`);
      } else if (key.includes("priceMax")) {
        activeFilters.push(`Prix max: ${value}`);
      }
    }

    this.filtersSummaryTarget.textContent =
      activeFilters.length > 0
        ? "Filtres actifs : " + activeFilters.join(", ")
        : "Aucun filtre actif";
  }
}
