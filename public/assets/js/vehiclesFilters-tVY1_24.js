// VehiclesFilter.js
export default class VehiclesFilter {
  constructor(formSelector = "#filters-form", debounceMs = 300) {
    this.form = document.querySelector(formSelector);
    if (!this.form) return;

    // Container principal (peut être le formulaire ou global)
    this.container = this.form.closest("[data-fetch-form]") || document;

    // Targets pour injection AJAX
    this.resultsTarget =
      this.container.querySelector("[data-target='vehicles-search-results']") ||
      document.querySelector("[data-target='vehicles-search-results']");

    this.paginationTopTarget =
      this.container.querySelector("[data-target='pagination-top']") ||
      document.querySelector("[data-target='pagination-top']");

    this.paginationBottomTarget =
      this.container.querySelector("[data-target='pagination-bottom']") ||
      document.querySelector("[data-target='pagination-bottom']");

    // URL de fetch depuis data-fetch-url
    this.fetchUrl =
      this.form.dataset.fetchUrl || this.container.dataset.fetchUrl;

    // Debounce
    this.debounceMs = debounceMs;
    this.debounceTimeout = null;

    this.bindEvents();
  }

  bindEvents() {
    // Submit classique
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.debounceSubmit();
    });

    // On change sur tout input / select
    this.form.querySelectorAll("input, select").forEach(el => {
      el.addEventListener("change", () => this.debounceSubmit());
    });

    // Pagination click
    this.container.addEventListener("click", e => {
      const link = e.target.closest(".pagination-link");
      if (link) {
        e.preventDefault();
        const page = link.dataset.page;
        this.submitFilters(page);
      }
    });

    // Sliders personnalisés (événements globaux)
    document.addEventListener("sliderChanged", e => {
      const { filter, min, max } = e.detail;
      const inputMin = this.form.querySelector(`input[name="${filter}Min"]`);
      const inputMax = this.form.querySelector(`input[name="${filter}Max"]`);
      if (inputMin) inputMin.value = min;
      if (inputMax) inputMax.value = max;
      this.debounceSubmit();
    });
  }

  debounceSubmit() {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(
      () => this.submitFilters(),
      this.debounceMs
    );
  }

  async submitFilters(page = 1) {
    if (!this.fetchUrl) return;

    // Collecte des filtres
    const formData = new FormData(this.form);
    const filters = {};
    formData.forEach((val, key) => {
      // Gestion tableaux
      if (filters[key]) filters[key] = [].concat(filters[key], val);
      else filters[key] = val;
    });

    try {
      const res = await fetch(`${this.fetchUrl}?page=${page}`, {
        method: "POST",
        body: JSON.stringify({ filters, q: filters.q || null }),
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      const data = await res.json();

      // Injection sécurisée
      if (this.resultsTarget && data.results !== undefined) {
        this.resultsTarget.innerHTML = data.results;
        this.resultsTarget.scrollIntoView({ behavior: "smooth" });
      }

      if (this.paginationTopTarget && data.paginationTop !== undefined) {
        this.paginationTopTarget.innerHTML = data.paginationTop;
      }

      if (this.paginationBottomTarget && data.paginationBottom !== undefined) {
        this.paginationBottomTarget.innerHTML = data.paginationBottom;
      }
    } catch (e) {
      console.error("VehiclesFilter AJAX error", e);
    }
  }
}
