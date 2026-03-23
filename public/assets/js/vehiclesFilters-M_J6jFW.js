// VehiclesFilter.js
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  /**
   * @param {string} formSelector - Sélecteur CSS du formulaire de filtres
   */
  constructor(formSelector = "#filters-form") {
    // On récupère le formulaire
    this.form = document.querySelector(formSelector);
    if (!this.form) {
      console.warn("VehiclesFilter : formulaire introuvable");
      return;
    }

    // Conteneur parent du formulaire (si besoin de ciblage spécifique)
    this.container = this.form.closest("[data-fetch-form]") || this.form;

    // Targets pour injection des résultats et de la pagination
    this.resultsTarget = document.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = document.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = document.querySelector(
      "[data-target='pagination-bottom']"
    );

    this.fetchUrl = this.form.dataset.fetchUrl;
    this.debounceTimeout = null;

    // Initialisation
    this.initSliders();
    this.bindEvents();
  }

  /**
   * Initialise tous les sliders présents dans le formulaire
   */
  initSliders() {
    this.form.querySelectorAll(".double-slider").forEach(slider => {
      initDoubleSlider(slider);

      // À chaque changement du slider, mettre à jour les inputs cachés et soumettre le filtre
      slider.addEventListener("sliderChanged", e => {
        const filter = slider.dataset.filter;
        const hiddenMin = this.form.querySelector(
          `input[name="filters[${filter}Min]"]`
        );
        const hiddenMax = this.form.querySelector(
          `input[name="filters[${filter}Max]"]`
        );
        if (hiddenMin && hiddenMax) {
          hiddenMin.value = e.detail.min;
          hiddenMax.value = e.detail.max;
        }
        this.debounceSubmit();
      });
    });
  }

  /**
   * Lie tous les événements : checkboxes, select, submit et pagination
   */
  bindEvents() {
    // Soumission formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.debounceSubmit();
    });

    // Changement sur inputs / select
    this.form.querySelectorAll("input, select").forEach(el => {
      el.addEventListener("change", () => this.debounceSubmit());
    });

    // Pagination click (gestion dynamique pour boutons ou liens)
    document.addEventListener("click", e => {
      const link = e.target.closest("[data-page]");
      if (link) {
        e.preventDefault();
        const page = Number.parseInt(link.dataset.page);
        if (!isNaN(page)) this.debounceSubmit(page);
      }
    });
  }

  /**
   * Debounce pour éviter d'envoyer trop de requêtes
   */
  debounceSubmit(page = 1, delay = 150) {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(page), delay);
  }

  /**
   * Récupère les valeurs du formulaire et envoie la requête AJAX
   */
  async submitFilters(page = 1) {
    if (!this.fetchUrl) return;

    const formData = new FormData(this.form);
    const filters = {};

    // Parcours de tous les inputs et conversion en objet filters attendu par Symfony
    formData.forEach((val, key) => {
      const match = key.match(/^filters\[(.+?)\](\[\])?$/);
      if (match) {
        const name = match[1];
        if (!filters[name]) filters[name] = [];
        filters[name].push(val);
      } else {
        // Cas des champs simples (pas dans filters[])
        filters[key] = val;
      }
    });

    // Débogage : afficher les filtres envoyés
    console.log("Filters envoyés :", filters);

    try {
      const res = await fetch(`${this.fetchUrl}?page=${page}`, {
        method: "POST",
        body: JSON.stringify({ filters, q: filters.q || null, page }),
        headers: {
          "Content-Type": "application/json"
        }
      });

      const data = await res.json();

      // Injection dans les targets
      if (this.resultsTarget) {
        this.resultsTarget.innerHTML = data.results;
      }
      if (this.paginationTopTarget) {
        this.paginationTopTarget.innerHTML = data.paginationTop;
      }
      if (this.paginationBottomTarget) {
        this.paginationBottomTarget.innerHTML = data.paginationBottom;
      }
    } catch (e) {
      console.error("VehiclesFilter AJAX error", e);
    }
  }
}
