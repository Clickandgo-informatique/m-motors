// vehiclesFilters.js

/**
 * Module pour gérer les filtres véhicules et la pagination AJAX
 */
export default class VehiclesFilter {
  /**
   * @param {HTMLElement} container - Le container du formulaire ou document
   */
  constructor(container = document) {
    // On vérifie si le container existe
    if (!container) {
      console.warn("VehiclesFilter : container introuvable");
      return;
    }
    this.container = container;

    // Sélecteur du formulaire de filtres
    this.form = this.container.querySelector("#filters-form");
    if (!this.form) {
      console.warn("VehiclesFilter : formulaire #filters-form introuvable");
      return;
    }

    // Champs checkbox et inputs
    this.inputs = this.form.querySelectorAll("input");

    // URL d’AJAX (data-fetch-url)
    this.url = this.form.dataset.fetchUrl;
    if (!this.url) {
      console.warn(
        "VehiclesFilter : data-fetch-url introuvable sur le formulaire"
      );
      return;
    }

    // Initialisation du slider
    this.initSlider();

    // Initialisation des événements
    this.initEvents();
  }

  /**
   * Initialisation du slider (double-slider)
   */
  initSlider() {
    const slider = this.form.querySelector(".double-slider");
    if (!slider) return;

    // Si le module rangeSelector est global
    if (typeof initDoubleSlider === "function") {
      initDoubleSlider(slider);

      // Ecoute le changement du slider pour mettre à jour les inputs cachés et soumettre
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
  }

  /**
   * Initialisation des événements (checkboxes, pagination)
   */
  initEvents() {
    // Écoute sur toutes les checkboxes
    this.inputs.forEach(input => {
      input.addEventListener("change", () => {
        this.submitFilters();
      });
    });

    // Pagination : événements délégués
    document.addEventListener("click", e => {
      if (!e.target.dataset.page) return;
      e.preventDefault();
      const page = parseInt(e.target.dataset.page);
      if (isNaN(page)) return;
      this.submitFilters(page);
    });
  }

  /**
   * Soumission des filtres via AJAX
   * @param {number} page - page à charger (facultatif)
   */
  async submitFilters(page = 1) {
    // Construction de l’objet filters
    const formData = new FormData(this.form);
    const filters = {};
    for (const [key, value] of formData.entries()) {
      // Gestion des tableaux
      if (key.endsWith("[]")) {
        const cleanKey = key.replace(/\[\]$/, "");
        filters[cleanKey] = filters[cleanKey] || [];
        filters[cleanKey].push(value);
      } else {
        filters[key] = value;
      }
    }

    // Corps JSON
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

      // Injection des fragments
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

// Initialisation automatique si le script est chargé
document.addEventListener("DOMContentLoaded", () => {
  new VehiclesFilter(document);
});
