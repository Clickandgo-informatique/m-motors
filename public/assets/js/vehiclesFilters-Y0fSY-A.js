// VehiclesFilter.js
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(formSelector = "#filters-form") {
    // Récupération du formulaire
    this.form = document.querySelector(formSelector);
    if (!this.form) return;

    // Conteneur principal (utile pour organisation globale)
    this.container = this.form.closest("[data-fetch-form]");
    if (!this.container) return;

    // Cibles pour injection dynamique des résultats et paginations
    this.resultsTarget = document.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = document.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = document.querySelector(
      "[data-target='pagination-bottom']"
    );

    // URL d’appel AJAX définie dans le HTML
    this.fetchUrl = this.form.dataset.fetchUrl;

    // Gestion du debounce (évite trop d’appels réseau)
    this.debounceTimeout = null;

    // Initialisation
    this.initSliders();
    this.bindEvents();
  }

  /**
   * Initialise les sliders double curseur
   * et écoute leur événement personnalisé
   */
  initSliders() {
    this.form.querySelectorAll(".double-slider").forEach(slider => {
      initDoubleSlider(slider);

      // Lorsqu’un slider change, on déclenche une soumission différée
      slider.addEventListener("sliderChanged", () => this.debounceSubmit());
    });
  }

  /**
   * Attache tous les événements nécessaires
   */
  bindEvents() {
    // Soumission classique du formulaire (empêchée)
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.debounceSubmit();
    });

    // Sur chaque changement d’input ou select, on relance la recherche
    this.form.querySelectorAll("input, select").forEach(el => {
      el.addEventListener("change", () => this.debounceSubmit());
    });

    // Gestion des clics sur la pagination (AJAX)
    document.addEventListener("click", e => {
      const link = e.target.closest(".pagination a[data-page]");
      if (link) {
        e.preventDefault();
        const page = Number.parseInt(link.dataset.page);
        this.debounceSubmit(page);
      }
    });

    // Scroll automatique vers les résultats après mise à jour
    this.scrollToResults = () => {
      if (this.resultsTarget) {
        this.resultsTarget.scrollIntoView({
          behavior: "smooth",
          block: "start"
        });
      }
    };
  }

  /**
   * Déclenche une soumission avec délai (debounce)
   * pour éviter les appels multiples trop rapides
   */
  debounceSubmit(page = 1, delay = 150) {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(page), delay);
  }

  /**
   * Construit les filtres et envoie la requête AJAX
   */
  async submitFilters(page = 1) {
    if (!this.fetchUrl) return;

    // Récupération des données du formulaire
    const formData = new FormData(this.form);
    const filters = {};

    formData.forEach((value, key) => {
      /**
       * Transformation des noms de champs Symfony :
       * filters[brand][] -> brand
       * filters[fuelType][] -> fuelType
       */
      const match = key.match(/^filters\[(.+?)\](\[\])?$/);

      if (match) {
        const cleanKey = match[1];

        // Initialisation du tableau si nécessaire
        if (!filters[cleanKey]) {
          filters[cleanKey] = [];
        }

        // Ajout de la valeur
        filters[cleanKey].push(value);
      } else {
        // Cas des champs simples (ex: q)
        filters[key] = value;
      }
    });

    // Extraction du champ de recherche texte
    const q = filters.q || null;
    delete filters.q;

    try {
      // Appel AJAX en JSON
      const res = await fetch(this.fetchUrl, {
        method: "POST",
        body: JSON.stringify({
          filters,
          q: q,
          page: page
        }),
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      const data = await res.json();

      // Injection des résultats
      if (this.resultsTarget) {
        this.resultsTarget.innerHTML = data.results;
      }

      // Injection pagination haute
      if (this.paginationTopTarget) {
        this.paginationTopTarget.innerHTML = data.paginationTop;
      }

      // Injection pagination basse
      if (this.paginationBottomTarget) {
        this.paginationBottomTarget.innerHTML = data.paginationBottom;
      }

      // Scroll vers les résultats
      this.scrollToResults();
    } catch (e) {
      console.error("VehiclesFilter AJAX error", e);
    }
  }
}
