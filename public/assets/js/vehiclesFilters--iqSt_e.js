// vehiclesFilters.js
import FilterBadges from "./FilterBadges.js";
import initDoubleSlider from "./rangeSelector.js";

/**
 * Gestion des filtres véhicules + pagination AJAX + badges + sliders avec debounce
 */
export default class VehiclesFilter {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.url = form.dataset.fetchUrl;
    if (!this.url) return;

    // Conteneurs principaux
    this.resultsContainer = document.querySelector(
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

    // Initialisation des badges côté client
    if (this.summaryContainer) {
      this.badges = new FilterBadges(
        this.summaryContainer,
        this.form,
        this.submitFilters.bind(this)
      );
    }

    // Initialisation des sliders
    this.initSliders();

    // Événements formulaire, pagination et badges
    this.initEvents();
  }

  // Initialisation des sliders (mileage, year, etc.)
  initSliders() {
    const sliders = this.form.querySelectorAll(".double-slider");
    if (!sliders.length || typeof initDoubleSlider !== "function") return;

    sliders.forEach(slider => {
      initDoubleSlider(slider);

      // Débounce pour limiter les requêtes
      let timer = null;
      slider.addEventListener("sliderChanged", e => {
        const { filter, min, max } = e.detail;

        // Mise à jour des inputs cachés
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

  // Initialisation des événements
  initEvents() {
    // Changement d'un input ou checkbox
    this.form.addEventListener("change", e => {
      if (!e.target.matches("input")) return;
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

    // Gestion du clic sur badges
    if (this.summaryContainer) {
      this.summaryContainer.addEventListener("click", e => {
        if (!e.target.matches(".badge-remove")) return;

        const filter = e.target.dataset.filter;
        const value = e.target.dataset.value;

        // Sliders (range)
        const slider = this.form.querySelector(
          `.double-slider[data-filter="${filter}"]`
        );
        if (slider) {
          // Reset via méthode exposée dans rangeSelector.js si dispo
          if (typeof slider.resetSlider === "function") {
            slider.resetSlider();
          } else {
            // fallback : remise à min/max
            const min = Number(slider.dataset.min);
            const max = Number(slider.dataset.max);
            const inputMin = this.form.querySelector(
              `input[name="filters[${filter}Min]"]`
            );
            const inputMax = this.form.querySelector(
              `input[name="filters[${filter}Max]"]`
            );
            if (inputMin) inputMin.value = min;
            if (inputMax) inputMax.value = max;

            slider.dispatchEvent(
              new CustomEvent("sliderChanged", {
                bubbles: true,
                detail: { filter, min, max }
              })
            );
          }
        } else {
          // Checkbox classiques
          const checkboxes = this.form.querySelectorAll(
            `input[name="filters[${filter}][]"]`
          );
          checkboxes.forEach(cb => {
            if (cb.value === value) cb.checked = false;
          });
        }

        // Mise à jour des badges
        if (this.badges) this.badges.updateBadges();

        // Relance du filtrage AJAX
        this.submitFilters();
      });
    }
  }

  // Soumission AJAX des filtres
  async submitFilters(page = 1) {
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

    try {
      const res = await fetch(this.url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters, page })
      });
      const data = await res.json();
      this.updateDOM(data);
    } catch (err) {
      console.error("Erreur AJAX :", err);
    }
  }

  // Injection des résultats dans le DOM
  updateDOM(data) {
    if (this.resultsContainer && data.results)
      this.resultsContainer.innerHTML = data.results;
    if (this.paginationTop && data.paginationTop)
      this.paginationTop.innerHTML = data.paginationTop;
    if (this.paginationBottom && data.paginationBottom)
      this.paginationBottom.innerHTML = data.paginationBottom;

    // Mise à jour des badges
    if (this.badges) this.badges.updateBadges();
  }
}

// Observer pour initialiser le formulaire dynamique
function watchFiltersForm() {
  const observer = new MutationObserver(() => {
    const form = document.querySelector("#filters-form");
    if (!form || form.dataset.initialized) return;
    form.dataset.initialized = "true";
    new VehiclesFilter(form);
  });

  observer.observe(document.body, { childList: true, subtree: true });
}

document.addEventListener("DOMContentLoaded", watchFiltersForm);
