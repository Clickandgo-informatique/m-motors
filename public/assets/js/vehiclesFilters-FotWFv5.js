// vehiclesFilters.js
import FilterBadges from "./FilterBadges.js";
import initDoubleSlider from "./rangeSelector.js";

/**
 * Gestion des filtres véhicules avec :
 * - pagination AJAX
 * - badges interactifs
 * - sliders double-handle (mileage, year, etc.)
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

  /**
   * Initialise tous les sliders présents dans le formulaire
   * et gère le debounce pour la soumission AJAX
   */
  initSliders() {
    const sliders = this.form.querySelectorAll(".double-slider");
    if (!sliders.length || typeof initDoubleSlider !== "function") return;

    sliders.forEach(slider => {
      // Initialise le slider via rangeSelector
      initDoubleSlider(slider);

      // Débounce pour limiter le nombre de requêtes
      let timer = null;

      // Événement custom déclenché par rangeSelector lors du changement de valeur
      slider.addEventListener("sliderChanged", e => {
        const { filter, min, max } = e.detail;

        // Mise à jour des inputs cachés
        if (slider.dataset.inputMin) {
          const inputMin = this.form.querySelector(
            `input[name="${slider.dataset.inputMin}"]`
          );
          if (inputMin) inputMin.value = min;
        }
        if (slider.dataset.inputMax) {
          const inputMax = this.form.querySelector(
            `input[name="${slider.dataset.inputMax}"]`
          );
          if (inputMax) inputMax.value = max;
        }

        // Débounce pour la soumission
        clearTimeout(timer);
        timer = setTimeout(() => this.submitFilters(), 300);
      });
    });
  }

  /**
   * Initialise les événements sur :
   * - le formulaire (checkbox / input)
   * - la pagination
   * - les badges (suppression d'un filtre)
   */
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

        // Sliders (range) : reset visuel + inputs cachés
        const slider = this.form.querySelector(
          `.double-slider[data-filter="${filter}"]`
        );
        if (slider && typeof slider.resetSlider === "function") {
          slider.resetSlider();
        } else {
          // Checkbox classiques : décocher la valeur correspondante
          const checkboxes = this.form.querySelectorAll(
            `input[name="filters[${filter}][]"]`
          );
          checkboxes.forEach(cb => {
            if (cb.value === value) cb.checked = false;
          });
        }

        // Mise à jour des badges côté client
        if (this.badges) this.badges.updateBadges();

        // Relance du filtrage AJAX
        this.submitFilters();
      });
    }
  }

  /**
   * Soumission AJAX des filtres
   */
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

  /**
   * Injection des résultats dans le DOM
   */
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

/**
 * Observer pour détecter l'ajout du formulaire dynamiquement
 */
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
