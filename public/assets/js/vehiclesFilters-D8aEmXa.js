// VehiclesFilter.js avec debounce
import initDoubleSlider from "./rangeSelector.js";

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

    this.onSliderChange = null;
    this.debouncedSubmit = this.debounce(this.submitFilters.bind(this), 300); // 300ms debounce

    this.initSliders();
    this.bindEvents();
  }

  // Débounce générique
  debounce(fn, delay) {
    let timer;
    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => fn(...args), delay);
    };
  }

  initSliders() {
    const sliders = this.form.querySelectorAll(".double-slider");
    sliders.forEach(slider => {
      if (!slider.dataset.initialized) {
        initDoubleSlider(slider);
        slider.dataset.initialized = "true";
      }
    });

    if (this.onSliderChange) {
      document.removeEventListener("sliderChanged", this.onSliderChange);
    }
    this.onSliderChange = e => this.debouncedSubmit();
    document.addEventListener("sliderChanged", this.onSliderChange);
  }

  bindEvents() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.debouncedSubmit();
    });

    this.form.querySelectorAll("input, select").forEach(el => {
      el.addEventListener("change", () => this.debouncedSubmit());
    });

    this.container.addEventListener("click", e => {
      const link = e.target.closest(".pagination-link");
      if (link) {
        e.preventDefault();
        const page = link.dataset.page;
        this.debouncedSubmit(page);
      }
    });
  }

  async submitFilters(page = 1) {
    if (!this.fetchUrl) return;

    const formData = new FormData(this.form);
    const filters = {};

    formData.forEach((val, key) => {
      if (filters[key]) filters[key] = [].concat(filters[key], val);
      else filters[key] = val;
    });

    this.form.querySelectorAll(".double-slider").forEach(slider => {
      const filter = slider.dataset.filter;
      const min = Number(slider.dataset.valueLow);
      const max = Number(slider.dataset.valueHigh);
      filters[filter] = { min, max };
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

      this.initSliders();
    } catch (e) {
      console.error("VehiclesFilter AJAX error", e);
    }
  }
}
