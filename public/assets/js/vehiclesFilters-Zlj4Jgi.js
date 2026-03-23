// vehiclesFilters.js

/**
 * Module de gestion des filtres véhicules + pagination AJAX + badges interactifs
 * Compatible avec formulaire chargé dynamiquement (fragment sidebar)
 */
import FilterBadges from "./FilterBadges.js";

export default class VehiclesFilter {
  /**
   * @param {HTMLFormElement} form
   */
  constructor(form) {
    console.log("INIT VehiclesFilter → élément reçu :", form);

    if (!(form instanceof HTMLFormElement)) {
      console.error("VehiclesFilter : élément invalide (pas un form)", form);
      return;
    }

    this.form = form;
    this.badges = null; // instance FilterBadges

    // URL AJAX obligatoire
    this.url = this.form.dataset.fetchUrl;
    if (!this.url) {
      console.error("VehiclesFilter : data-fetch-url manquant");
      return;
    }

    // Initialisation slider si présent
    this.initSlider();

    // Initialisation événements (checkbox, pagination)
    this.initEvents();

    // Initialisation des badges si le container existe
    const summaryContainer = this.form.querySelector(
      '[data-target="filters-summary"]'
    );
    if (summaryContainer) {
      this.initBadges(summaryContainer);
    }
  }

  /**
   * Initialisation du slider (double-slider)
   */
  initSlider() {
    const slider = this.form.querySelector(".double-slider");
    if (!slider || typeof initDoubleSlider !== "function") return;

    initDoubleSlider(slider);

    document.addEventListener("sliderChanged", e => {
      const { filter, min, max } = e.detail;
      const inputMin = this.form.querySelector(
        `input[name="filters[${filter}Min]"]`
      );
      const inputMax = this.form.querySelector(
        `input[name="filters[${filter}Max]"]`
      );
      if (inputMin && inputMax) {
        inputMin.value = min;
        inputMax.value = max;
      }
      this.submitFilters();
    });
  }

  /**
   * Initialisation des événements
   */
  initEvents() {
    // Changement de valeur dans le formulaire
    this.form.addEventListener("change", e => {
      if (!e.target.matches("input")) return;
      this.submitFilters();
    });

    // Pagination (event delegation)
    document.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;
      e.preventDefault();
      const page = Number.parseInt(btn.dataset.page);
      if (Number.isNaN(page)) return;
      this.submitFilters(page);
    });
  }

  /**
   * Initialise ou réinitialise les badges
   * @param {HTMLElement} container
   */
  initBadges(container) {
    // Détruit l'ancienne instance si elle existe
    if (this.badges) {
      this.badges.destroy();
      this.badges = null;
    }

    this.badges = new FilterBadges(
      container,
      this.form,
      this.submitFilters.bind(this)
    );
  }

  /**
   * Construit les filtres et envoie la requête AJAX
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
      } else {
        filters[name] = value;
      }
    }

    console.log("Filters envoyés :", filters);

    try {
      const response = await fetch(this.url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters, page })
      });

      const data = await response.json();
      this.updateDOM(data);
    } catch (err) {
      console.error("Erreur AJAX :", err);
    }
  }

  /**
   * Met à jour le DOM avec les fragments retournés
   */
  updateDOM(data) {
    // Résultats véhicules
    const results = this.form.querySelector(
      '[data-target="vehicles-search-results"]'
    );
    if (results && data.results) results.innerHTML = data.results;

    // Pagination Top
    const top = this.form.querySelector('[data-target="pagination-top"]');
    if (top && data.paginationTop) top.innerHTML = data.paginationTop;

    // Pagination Bottom
    const bottom = this.form.querySelector('[data-target="pagination-bottom"]');
    if (bottom && data.paginationBottom)
      bottom.innerHTML = data.paginationBottom;

    // Mise à jour du contenu des badges sans réinitialiser l'instance
    const summary = this.form.querySelector('[data-target="filters-summary"]');
    if (summary) {
      summary.innerHTML =
        data.filtersSummary?.trim() ||
        '<p class="text-center text-muted">Aucun filtre appliqué</p>';
    }
  }
}

/**
 * Observer → détecte l’apparition du formulaire dans le DOM
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
