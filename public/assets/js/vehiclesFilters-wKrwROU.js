// vehiclesFilters.js

/**
 * Module pour gérer les filtres véhicules et la pagination AJAX
 */
export default class VehiclesFilter {
  /**
   * @param {HTMLElement} container - Le formulaire ou le fragment contenant le formulaire
   */
  constructor(container) {
    if (!container) {
      console.warn("VehiclesFilter : container introuvable");
      return;
    }

    // On récupère le formulaire à l'intérieur du container
    this.form = container.querySelector("#filters-form");
    if (!this.form) {
      console.warn(
        "VehiclesFilter : formulaire #filters-form introuvable dans le container"
      );
      return;
    }

    // Inputs et checkboxes du formulaire
    this.inputs = this.form.querySelectorAll("input");

    // URL d’AJAX pour les filtres
    this.url = this.form.dataset.fetchUrl;
    if (!this.url) {
      console.warn(
        "VehiclesFilter : data-fetch-url introuvable sur le formulaire"
      );
      return;
    }

    // Initialisation du slider si présent
    this.initSlider();

    // Initialisation des événements (checkboxes et pagination)
    this.initEvents();

    console.log("VehiclesFilter : initialisé sur", this.form);
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

      // Écoute le changement du slider pour mettre à jour les inputs cachés et soumettre
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
    // Écoute sur toutes les checkboxes et inputs
    this.inputs.forEach(input => {
      input.addEventListener("change", () => {
        console.log("VehiclesFilter : input modifié", input.name, input.value);
        this.submitFilters();
      });
    });

    // Pagination : écoute déléguée sur tout le document
    document.addEventListener("click", e => {
      const target = e.target;
      if (!target.dataset.page) return;
      e.preventDefault();
      const page = parseInt(target.dataset.page);
      if (isNaN(page)) return;
      console.log("VehiclesFilter : pagination page", page);
      this.submitFilters(page);
    });
  }

  /**
   * Soumission des filtres via AJAX
   * @param {number} page - page à charger (facultatif)
   */
  async submitFilters(page = 1) {
    // Construction de l’objet filters depuis le formulaire
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

    // Payload JSON
    const payload = { filters, q: null, page };

    console.log("VehiclesFilter : envoi AJAX", payload);

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
      console.log("VehiclesFilter : réponse AJAX", data);

      this.updateResults(data, filters);
    } catch (err) {
      console.error("VehiclesFilter : erreur AJAX", err);
    }
  }

  /**
   * Injection des fragments reçus via AJAX
   * @param {Object} data - JSON retourné par le controller
   * @param {Object} filters - filtres envoyés pour construire le résumé
   */
  updateResults(data, filters) {
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

    // Résumé des filtres (injecté depuis le fragment _filters_summary.html.twig)
    const summary = document.querySelector('[data-target="filters-summary"]');
    if (summary) {
      // Si le controller renvoie déjà le résumé
      if (data.filtersSummary) {
        summary.innerHTML = data.filtersSummary;
      } else {
        // Sinon construire un résumé basique depuis les filtres envoyés
        const summaryText = Object.entries(filters)
          .map(([key, val]) => {
            if (Array.isArray(val)) {
              return `${key} : ${val.join(", ")}`;
            }
            return `${key} : ${val}`;
          })
          .join(" | ");
        summary.innerHTML = summaryText || "Aucun filtre appliqué";
      }
    }
  }
}

/**
 * Fonction utilitaire pour initialiser VehiclesFilter sur un fragment
 * @param {HTMLElement} container - fragment contenant le formulaire
 */
export function initVehiclesFilter(container) {
  if (!container) return;
  const form = container.querySelector("#filters-form");
  if (form) {
    new VehiclesFilter(container);
  } else {
    console.warn(
      "initVehiclesFilter : formulaire introuvable dans le container"
    );
  }
}
