// vehiclesFilters.js

/**
 * Module pour gérer les filtres véhicules et la pagination AJAX
 */
export default class VehiclesFilter {
  /**
   * @param {HTMLElement|Document} container - Le container du formulaire ou document
   */
  constructor(container = document) {
    console.log("VehiclesFilter : initialisation");

    // Vérifie que le container est correct
    if (!container) {
      console.warn("VehiclesFilter : container introuvable");
      return;
    }
    this.container = container;

    // Sélecteur du formulaire de filtres
    this.form = this.container.querySelector("#filters-form");
    if (!this.form) {
      console.warn(
        "VehiclesFilter : formulaire #filters-form introuvable dans le container"
      );
      return;
    }
    console.log("VehiclesFilter : formulaire trouvé", this.form);

    // Tous les inputs (checkbox, hidden, etc.)
    this.inputs = this.form.querySelectorAll("input");

    // URL AJAX pour envoyer les filtres
    this.url = this.form.dataset.fetchUrl;
    if (!this.url) {
      console.warn(
        "VehiclesFilter : data-fetch-url introuvable sur le formulaire"
      );
      return;
    }
    console.log("VehiclesFilter : URL AJAX", this.url);

    // Initialisation du slider si présent
    this.initSlider();

    // Initialisation des événements (checkboxes et pagination)
    this.initEvents();
  }

  /**
   * Initialisation du slider (double-slider)
   */
  initSlider() {
    const slider = this.form.querySelector(".double-slider");
    if (!slider) {
      console.log("VehiclesFilter : pas de slider présent");
      return;
    }
    console.log("VehiclesFilter : slider trouvé", slider);

    // Vérifie que la fonction initDoubleSlider existe
    if (typeof initDoubleSlider === "function") {
      initDoubleSlider(slider);

      // Écoute globale pour les changements du slider
      document.addEventListener("sliderChanged", e => {
        const { filter, min, max } = e.detail;
        console.log("Slider changé :", filter, min, max);

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

        // Re-déclenche le filtrage AJAX
        this.submitFilters();
      });
    }
  }

  /**
   * Initialisation des événements
   */
  initEvents() {
    // Écoute sur toutes les checkboxes et inputs
    this.inputs.forEach(input => {
      input.addEventListener("change", () => {
        console.log("Input modifié :", input.name, input.value);
        this.submitFilters();
      });
    });

    // Pagination : écoute déléguée sur tout le document
    document.addEventListener("click", e => {
      if (!e.target.dataset.page) return;
      e.preventDefault();
      const page = parseInt(e.target.dataset.page);
      if (isNaN(page)) return;
      console.log("Pagination cliquée : page", page);
      this.submitFilters(page);
    });
  }

  /**
   * Soumission des filtres via AJAX
   * @param {number} page - page à charger (facultatif)
   */
  async submitFilters(page = 1) {
    console.log("VehiclesFilter : soumission AJAX page", page);

    // Construction de l'objet filters depuis le formulaire
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
    console.log("VehiclesFilter : filters construits", filters);

    // Payload JSON
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
      console.log("VehiclesFilter : réponse AJAX reçue", data);

      // Injection des fragments HTML
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

// Initialisation automatique au chargement du DOM
document.addEventListener("DOMContentLoaded", () => {
  new VehiclesFilter(document);
});
