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
      return; // Stop si container manquant
    }

    // Cibles dynamiques
    this.targets = {};
    this.container.querySelectorAll("[data-target]").forEach(el => {
      const key = el.dataset.target;
      if (!this.targets[key]) this.targets[key] = [];
      this.targets[key].push(el);
    });

    // Endpoint AJAX
    this.fetchUrl = this.container.dataset.fetchUrl;

    this.bindEvents();
  }

  bindEvents() {
    // Exemple : submit filtre AJAX
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.submitFilters();
    });
  }

  async submitFilters() {
    if (!this.fetchUrl) return;

    const formData = new FormData(this.form);
    const params = new URLSearchParams(formData).toString();
    const url = `${this.fetchUrl}?${params}`;

    try {
      const response = await fetch(url, {
        headers: { "X-Requested-With": "XMLHttpRequest" }
      });
      const data = await response.text();

      // Injection dans le target principal
      if (this.targets["vehicles-search-results"]) {
        this.targets["vehicles-search-results"].forEach(el => {
          el.innerHTML = data;
        });
      }
    } catch (e) {
      console.error("VehiclesFilter AJAX error", e);
    }
  }
}
