// assets/js/modules/VehiclesFilter.js
export default class VehiclesFilter {
  constructor(formSelector = "#filters-form") {
    this.form = document.querySelector(formSelector);
    if (!this.form) return;

    // Container pour data-* et pagination
    this.container = this.form.closest("[data-fetch-form]") || this.form;
    this.resultsTarget = this.container.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = this.container.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = this.container.querySelector(
      "[data-target='pagination-bottom']"
    );
    this.fetchUrl = this.container.dataset.fetchUrl;

    if (!this.fetchUrl) {
      console.warn("VehiclesFilter: data-fetch-url manquant sur le container.");
      return;
    }

    this.bindEvents();
  }

  bindEvents() {
    // Soumission formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.submitFilters();
    });

    // Changement sur input/select
    this.form.querySelectorAll("input, select").forEach(el => {
      el.addEventListener("change", () => this.submitFilters());
    });

    // Pagination click
    this.container.addEventListener("click", e => {
      const link = e.target.closest(".pagination-link");
      if (link) {
        e.preventDefault();
        const page = link.dataset.page || 1;
        this.submitFilters(page);
      }
    });
  }

  /**
   * Transforme FormData en objet compatible Symfony
   * gère les checkboxes avec name ending by [].
   */
  serializeForm() {
    const formData = new FormData(this.form);
    const filters = {};

    for (let [key, value] of formData.entries()) {
      if (key.endsWith("[]")) {
        key = key.slice(0, -2);
        filters[key] = filters[key] || [];
        filters[key].push(value);
      } else {
        filters[key] = value;
      }
    }

    return { filters };
  }

  async submitFilters(page = 1) {
    if (!this.fetchUrl) return;

    const payload = this.serializeForm();
    payload.page = page;

    try {
      const res = await fetch(`${this.fetchUrl}`, {
        method: "POST",
        body: JSON.stringify(payload),
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      if (!res.ok) {
        console.error("VehiclesFilter AJAX error: HTTP", res.status);
        return;
      }

      const data = await res.json();

      if (this.resultsTarget && data.results !== undefined)
        this.resultsTarget.innerHTML = data.results;
      if (this.paginationTopTarget && data.paginationTop !== undefined)
        this.paginationTopTarget.innerHTML = data.paginationTop;
      if (this.paginationBottomTarget && data.paginationBottom !== undefined)
        this.paginationBottomTarget.innerHTML = data.paginationBottom;
    } catch (e) {
      console.error("VehiclesFilter AJAX error", e);
    }
  }
}
