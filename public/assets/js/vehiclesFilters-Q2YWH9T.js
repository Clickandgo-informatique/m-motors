// vehiclesFilters.js

/**
 * Module de gestion des filtres véhicules + pagination AJAX
 * Compatible avec formulaire chargé dynamiquement (fragment sidebar)
 */
export default class VehiclesFilter {
  /**
   * @param {HTMLFormElement} form
   */
  constructor(form) {
    console.log("INIT VehiclesFilter → élément reçu :", form);

    // Sécurité : vérifier que c’est bien un <form>
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

    // Initialisation slider si présent
    this.initSlider();

    // Initialisation des events (checkboxes, pagination)
    this.initEvents();
  }

  /**
   * Initialisation du slider (double-slider)
   * Fonction globale initDoubleSlider() requise
   */
  initSlider() {
    const slider = this.form.querySelector(".double-slider");
    if (!slider || typeof initDoubleSlider !== "function") return;

    initDoubleSlider(slider);

    // Écoute du slider pour mise à jour inputs cachés
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
      this.submitFilters(); // déclenche filtrage AJAX
    });
  }

  /**
   * Initialise tous les événements
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
   * @param {number} page
   */
  async submitFilters(page = 1) {
    const formData = new FormData(this.form);
    const filters = {};

    // Transformation des clés "filters[xxx]" → "xxx"
    for (const [key, value] of formData.entries()) {
      const match = key.match(/^filters\[(.+?)\](\[\])?$/);
      if (!match) continue;

      const name = match[1]; // "brand", "bodyType", "mileageMin"...
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
    const results = document.querySelector("#vehicles-results");
    if (results && data.results) results.innerHTML = data.results;

    // Pagination Top
    const top = document.querySelector(".pagination-wrapper.top");
    if (top && data.paginationTop) top.innerHTML = data.paginationTop;

    // Pagination Bottom
    const bottom = document.querySelector(".pagination-wrapper.bottom");
    if (bottom && data.paginationBottom)
      bottom.innerHTML = data.paginationBottom;

    // Résumé des filtres
    const summary = document.querySelector('[data-target="filters-summary"]');
    if (summary && data.filtersSummary) summary.innerHTML = data.filtersSummary;
  }
}

/**
 * Observer → détecte l’apparition du formulaire dans le DOM
 * (utile car sidebar est un fragment chargé dynamiquement)
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

/**
 * Initialisation au chargement de la page
 */
document.addEventListener("DOMContentLoaded", watchFiltersForm);
