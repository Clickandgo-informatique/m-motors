// assets/js/vehiclesFilters.js

export default class VehiclesFilter {
  constructor(formSelector = "[data-filter-form]") {
    // Récupération du formulaire
    this.form = document.querySelector(formSelector);
    if (!this.form) {
      console.warn("VehiclesFilter : formulaire introuvable", formSelector);
      return;
    }

    // Cherche le parent fetch-form
    this.container = this.form.closest("[data-fetch-form]");
    if (!this.container) {
      console.warn(
        "VehiclesFilter : parent [data-fetch-form] introuvable pour",
        this.form
      );
      return;
    }

    // Targets dynamiques : tbody et pagination
    this.targets = {};
    this.container.querySelectorAll("[data-target]").forEach(el => {
      const key = el.dataset.target;
      if (!this.targets[key]) this.targets[key] = [];
      this.targets[key].push(el);
    });

    // Endpoint AJAX
    this.fetchUrl = this.container.dataset.fetchUrl;

    // Bind events
    this.bindEvents();
  }

  bindEvents() {
    if (!this.form) return;

    // Soumission classique du formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.submitFilters();
    });

    // Déclenchement sur changement d'input / checkbox / select
    this.form.querySelectorAll("input, select").forEach(input => {
      input.addEventListener("change", () => this.submitFilters());
    });
  }

  async submitFilters() {
    if (!this.fetchUrl) return;

    // Récupère les filtres
    const formData = new FormData(this.form);
    const filters = {};
    formData.forEach((value, key) => {
      // Les checkbox multiples → tableau
      if (filters[key]) {
        filters[key] = [].concat(filters[key], value);
      } else {
        filters[key] = value;
      }
    });

    try {
      const response = await fetch(this.fetchUrl, {
        method: "POST",
        body: JSON.stringify({ filters, q: filters.q || null }),
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      if (!response.ok) throw new Error("Erreur réseau");

      const data = await response.json();

      // Injection du HTML des résultats
      if (this.targets["vehicles-search-results"]) {
        this.targets["vehicles-search-results"].forEach(el => {
          el.innerHTML = data.results;
        });
      }

      // Injection du HTML de la pagination
      if (this.targets["pagination-info"]) {
        this.targets["pagination-info"].forEach(el => {
          el.innerHTML = data.pagination;
        });
      }
    } catch (e) {
      console.error("VehiclesFilter AJAX error", e);
    }
  }
}
