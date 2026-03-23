// VehiclesFilter.js

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

    // URL AJAX obligatoire
    this.url = this.form.dataset.fetchUrl;
    if (!this.url) {
      console.error("VehiclesFilter : data-fetch-url manquant");
      return;
    }

    // Conteneur badges
    this.summaryContainer = this.form.querySelector(
      '[data-target="filters-summary"]'
    );

    // Initialisation slider
    this.initSlider();

    // Initialisation événements
    this.initEvents();

    // Initialisation des badges
    if (this.summaryContainer) {
      this.badges = new FilterBadges(
        this.summaryContainer,
        this.form,
        this.submitFilters.bind(this)
      );
    }
  }

  /**
   * Initialisation du double-slider
   */
  initSlider() {
    const slider = this.form.querySelector(".double-slider");
    if (!slider || typeof initDoubleSlider !== "function") return;

    initDoubleSlider(slider);
    slider.dataset.initialized = "true";

    // Écoute l'événement custom sliderChanged
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
   * Initialisation des événements (form change + pagination + badges)
   */
  initEvents() {
    // 1. Changement de valeur dans le formulaire
    this.form.addEventListener("change", e => {
      if (!e.target.matches("input")) return;
      this.submitFilters();
    });

    // 2. Pagination (event delegation)
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
      this.updateDOM(data, filters);
    } catch (err) {
      console.error("Erreur AJAX :", err);
    }
  }

  /**
   * Met à jour le DOM avec les résultats, la pagination et les badges
   */
  updateDOM(data, filters = {}) {
    // Résultats véhicules
    const results = this.form.querySelector(
      '[data-target="vehicles-search-results"]'
    );
    if (results && data.results) results.innerHTML = data.results;

    // Pagination
    const top = this.form.querySelector('[data-target="pagination-top"]');
    if (top && data.paginationTop) top.innerHTML = data.paginationTop;

    const bottom = this.form.querySelector('[data-target="pagination-bottom"]');
    if (bottom && data.paginationBottom)
      bottom.innerHTML = data.paginationBottom;

    // Double-slider (réinitialisation si nécessaire)
    const slider = this.form.querySelector(".double-slider");
    if (
      slider &&
      typeof initDoubleSlider === "function" &&
      !slider.dataset.initialized
    ) {
      initDoubleSlider(slider);
      slider.dataset.initialized = "true";
    }

    // Badges de filtres
    if (this.summaryContainer) {
      // Génère le HTML des badges à partir des filtres actuels
      if (Object.keys(filters).length > 0) {
        this.summaryContainer.innerHTML = Object.entries(filters)
          .map(([key, value]) => {
            if (Array.isArray(value)) {
              return value
                .map(
                  v =>
                    `<span class="badge" data-filter="${key}" data-value="${v}">
                  ${key}: ${v} <span class="badge-remove" data-filter="${key}" data-value="${v}">&times;</span>
                </span>`
                )
                .join(" ");
            } else {
              return `<span class="badge" data-filter="${key}" data-value="${value}">
                        ${key}: ${value} <span class="badge-remove" data-filter="${key}" data-value="${value}">&times;</span>
                      </span>`;
            }
          })
          .join(" ");
      } else {
        this.summaryContainer.innerHTML =
          '<p class="text-center text-muted">Aucun filtre appliqué</p>';
      }

      // Réinitialise les événements de FilterBadges
      if (this.badges) {
        this.badges.destroy();
        this.badges.init();
      }
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
