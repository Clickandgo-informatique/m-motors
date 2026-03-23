export default class VehiclesFilter {
  constructor(formSelector = "#filters-form") {
    this.form = document.querySelector(formSelector);
    if (!this.form) return;

    this.container = this.form.closest("[data-fetch-form]");
    if (!this.container) return;

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

    this.bindEvents();
  }

  bindEvents() {
    // Submit classique
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.submitFilters();
    });

    // Changement automatique pour input, checkbox, select
    this.form.querySelectorAll("input, select").forEach(el => {
      el.addEventListener("change", () => this.submitFilters());
    });
  }

  async submitFilters() {
    if (!this.fetchUrl) return;

    const formData = new FormData(this.form);
    const filters = {};
    formData.forEach((val, key) => {
      if (filters[key]) filters[key] = [].concat(filters[key], val);
      else filters[key] = val;
    });

    try {
      const res = await fetch(this.fetchUrl, {
        method: "POST",
        body: JSON.stringify({ filters, q: filters.q || null }),
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      const data = await res.json();

      if (this.resultsTarget) this.resultsTarget.innerHTML = data.results;
      if (this.paginationTopTarget)
        this.paginationTopTarget.innerHTML = data.paginationTop;
      if (this.paginationBottomTarget)
        this.paginationBottomTarget.innerHTML = data.paginationBottom;
    } catch (e) {
      console.error("VehiclesFilter AJAX error", e);
    }
  }
}
