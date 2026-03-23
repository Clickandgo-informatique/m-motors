import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(containerSelector = "[data-fetch-form]") {
    this.container = document.querySelector(containerSelector);
    if (!this.container) return;

    this.form = this.container.querySelector("form");
    this.fetchUrl = this.form.dataset.fetchUrl;

    this.resultsTarget = this.container.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = this.container.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = this.container.querySelector(
      "[data-target='pagination-bottom']"
    );

    this.debounceTimeout = null;

    this.initSliders(); // sliders init au premier chargement
    this.bindEvents(); // délégation globale
  }

  initSliders() {
    // Tous les sliders présents dans le formulaire ou injectés
    this.container.querySelectorAll(".double-slider").forEach(slider => {
      initDoubleSlider(slider);
      slider.addEventListener("sliderChanged", () => this.debounceSubmit());
    });
  }

  bindEvents() {
    // Form submit
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.debounceSubmit();
    });

    // Delegation pour tous inputs et selects
    this.container.addEventListener("change", e => {
      if (e.target.matches("input, select")) this.debounceSubmit();
    });

    // Pagination delegation
    this.container.addEventListener("click", e => {
      const link = e.target.closest("[data-page]");
      if (link) {
        e.preventDefault();
        const page = parseInt(link.dataset.page);
        this.debounceSubmit(page);
      }
    });
  }

  debounceSubmit(page = 1, delay = 200) {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(page), delay);
  }

  async submitFilters(page = 1) {
    if (!this.fetchUrl) return;

    const formData = new FormData(this.form);
    const filters = {};
    formData.forEach((val, key) => {
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

      if (this.resultsTarget) this.resultsTarget.innerHTML = data.results;
      if (this.paginationTopTarget)
        this.paginationTopTarget.innerHTML = data.paginationTop;
      if (this.paginationBottomTarget)
        this.paginationBottomTarget.innerHTML = data.paginationBottom;

      this.initSliders(); // réinitialise sliders après injection
      this.resultsTarget.scrollIntoView({ behavior: "smooth", block: "start" });
    } catch (e) {
      console.error("AJAX error", e);
    }
  }
}
