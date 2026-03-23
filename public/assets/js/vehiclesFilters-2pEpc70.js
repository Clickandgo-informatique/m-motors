// vehiclesFilters.js

/**
 * Module pour gérer les filtres véhicules et la pagination AJAX
 */
export default class VehiclesFilter {
  /**
   * @param {HTMLElement|Document} container - Le container du formulaire ou document
   */
  constructor(container = document) {
    // Vérification du container
    if (
      !container ||
      !(container instanceof HTMLElement || container instanceof Document)
    ) {
      console.warn("VehiclesFilter : container invalide", container);
      return;
    }
    this.container = container;
    console.log("VehiclesFilter initialisé avec container :", container);

    // Sélecteur du formulaire de filtres
    this.form = this.container.querySelector("#filters-form");
    if (!this.form) {
      console.warn("VehiclesFilter : formulaire #filters-form introuvable");
      return;
    }

    console.log("Formulaire trouvé :", this.form);

    // Tous les inputs (checkboxes, hidden, etc.)
    this.inputs = this.form.querySelectorAll("input");

    // URL d’AJAX (data-fetch-url)
    this.url = this.form.dataset.fetchUrl;
    if (!this.url) {
      console.warn(
        "VehiclesFilter : data-fetch-url introuvable sur le formulaire"
      );
      return;
    }
    console.log("URL AJAX :", this.url);

    // Initialisation du slider
    this.initSlider();

    // Initialisation des événements (checkboxes + pagination)
    this.initEvents();
  }

  /**
   * Initialisation du slider double
   */
  initSlider() {
    const slider = this.form.querySelector(".double-slider");
    if (!slider) {
      console.log("Aucun double-slider trouvé dans le formulaire");
      return;
    }

    console.log("Double-slider trouvé :", slider);

    // Si la fonction initDoubleSlider est définie globalement
    if (typeof initDoubleSlider === "function") {
      initDoubleSlider(slider);

      // Écoute l'événement sliderChanged pour mettre à jour les inputs cachés
      document.addEventListener("sliderChanged", e => {
        const { filter, min, max } = e.detail;
        console.log("SliderChanged :", filter, min, max);

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

        this.submitFilters(); // déclenche le filtrage AJAX
      });
    } else {
      console.warn("initDoubleSlider n'est pas défini !");
    }
  }

  /**
   * Initialisation des événements sur les inputs et la pagination
   */
  initEvents() {
    console.log("Initialisation des événements sur les inputs");

    // Écoute sur toutes les checkboxes et inputs
    this.inputs.forEach(input => {
      input.addEventListener("change", () => {
        console.log("Input changé :", input.name, input.value);
        this.submitFilters();
      });
    });

    // Pagination : écoute déléguée sur le document
    document.addEventListener("click", e => {
      if (!e.target.dataset.page) return;
      e.preventDefault();

      const page = parseInt(e.target.dataset.page);
      if (isNaN(page)) return;

      console.log("Pagination click sur page :", page);
      this.submitFilters(page);
    });
  }

  /**
   * Soumission des filtres via AJAX
   * @param {number} page - page à charger (facultatif)
   */
  async submitFilters(page = 1) {
    console.log("Soumission filtres page :", page);

    // Construction de l’objet filters
    const formData = new FormData(this.form);
    const filters = {};

    for (const [key, value] of formData.entries()) {
      if (key.endsWith("[]")) {
        const cleanKey = key.replace(/\[\]$/, "");
        filters[cleanKey] = filters[cleanKey] || [];
        filters[cleanKey].push(value);
      } else {
        filters[key] = value;
      }
    }

    console.log("Filters construits :", filters);

    // Payload JSON pour l'AJAX
    const payload = {
      filters,
      q: null,
      page
    };

    try {
      const res = await fetch(this.url, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json();
      console.log("Réponse AJAX reçue :", data);

      // Injection des fragments reçus
      this.updateResults(data);
    } catch (err) {
      console.error("VehiclesFilter : erreur AJAX", err);
    }
  }

  /**
   * Injection des fragments reçus via AJAX
   * @param {Object} data - JSON retourné par le controller
   */
  updateResults(data) {
    console.log("Mise à jour des résultats");

    // Résultats véhicules
    const resultsContainer = document.querySelector("#vehicles-results");
    if (resultsContainer && data.results) {
      resultsContainer.innerHTML = data.results;
    }

    // Pagination Top
    const paginationTop = document.querySelector(".pagination-wrapper.top");
    if (paginationTop && data.paginationTop) {
      paginationTop.innerHTML = data.paginationTop;
    }

    // Pagination Bottom
    const paginationBottom = document.querySelector(
      ".pagination-wrapper.bottom"
    );
    if (paginationBottom && data.paginationBottom) {
      paginationBottom.innerHTML = data.paginationBottom;
    }

    // Résumé des filtres
    const summary = document.querySelector('[data-target="filters-summary"]');
    if (summary && data.filtersSummary) {
      summary.innerHTML = data.filtersSummary;
    }
  }
}

// Initialisation automatique
document.addEventListener("DOMContentLoaded", () => {
  new VehiclesFilter(document);
});
