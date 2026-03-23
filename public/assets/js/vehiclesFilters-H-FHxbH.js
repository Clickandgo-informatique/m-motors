// VehiclesFilter.js
import FilterBadges from "./FilterBadges.js";
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(form) {
    console.log("INIT VehiclesFilter → élément reçu :", form);

    if (!(form instanceof HTMLFormElement)) {
      console.error("VehiclesFilter : élément invalide (pas un form)", form);
      return;
    }

    if (form.dataset.initialized) {
      console.log("VehiclesFilter déjà initialisé pour ce formulaire");
      return;
    }
    form.dataset.initialized = "true";

    this.form = form;
    this.url = this.form.dataset.fetchUrl;
    if (!this.url) {
      console.error("VehiclesFilter : data-fetch-url manquant");
      return;
    }

    // Badges
    const summaryContainer = this.form.querySelector(
      '[data-target="filters-summary"]'
    );
    if (summaryContainer) {
      this.badges = new FilterBadges(
        summaryContainer,
        this.form,
        this.submitFilters.bind(this)
      );
    }

    // Slider
    this.initSlider();

    // Events
    this.initEvents();
  }

  initSlider() {
    const slider = this.form.querySelector(".double-slider");
    if (!slider || typeof initDoubleSlider !== "function") return;
    initDoubleSlider(slider);

    // Slider custom event
    this._sliderHandler = e => {
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
      this.submitFilters();
    };

    slider.addEventListener("sliderChanged", this._sliderHandler);
  }

  initEvents() {
    // Change inputs dans le formulaire
    this._changeHandler = e => {
      if (!e.target.matches("input")) return;
      this.submitFilters();
    };
    this.form.addEventListener("change", this._changeHandler);

    // Pagination
    this._clickHandler = e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;
      e.preventDefault();
      const page = Number.parseInt(btn.dataset.page);
      if (!isNaN(page)) this.submitFilters(page);
    };
    this.form.addEventListener("click", this._clickHandler);
  }

  async submitFilters(page = 1) {
    const formData = new FormData(this.form);
    const filters = {};
    for (const [key, value] of formData.entries()) {
      const match = key.match(/^filters\[(.+?)\](\[\])?$/);
      if (!match) continue;
      const name = match[1];
      const isArray = !!match[2];
      if (isArray) {
        if (!filters[name]) filters[name] = [];
        filters[name].push(value);
      } else {
        filters[name] = value;
      }
    }

    try {
      const response = await fetch(this.url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters, page })
      });
      const data = await response.json();
      this.updateDOM(data);
    } catch (err) {
      console.error("Erreur AJAX :", err);
    }
  }

  updateDOM(data) {
    const results = this.form.querySelector(
      '[data-target="vehicles-search-results"]'
    );
    if (results && data.results) results.innerHTML = data.results;

    const top = this.form.querySelector('[data-target="pagination-top"]');
    if (top && data.paginationTop) top.innerHTML = data.paginationTop;

    const bottom = this.form.querySelector('[data-target="pagination-bottom"]');
    if (bottom && data.paginationBottom)
      bottom.innerHTML = data.paginationBottom;

    const summary = this.form.querySelector('[data-target="filters-summary"]');
    if (summary) {
      if (data.filtersSummary && data.filtersSummary.trim() !== "") {
        summary.innerHTML = data.filtersSummary;
      } else {
        summary.innerHTML =
          '<p class="text-center text-muted">Aucun filtre appliqué</p>';
      }
    }
  }

  destroy() {
    if (this.badges) this.badges.destroy();
    if (this._changeHandler)
      this.form.removeEventListener("change", this._changeHandler);
    if (this._clickHandler)
      this.form.removeEventListener("click", this._clickHandler);

    const slider = this.form.querySelector(".double-slider");
    if (slider && this._sliderHandler)
      slider.removeEventListener("sliderChanged", this._sliderHandler);
    console.log("VehiclesFilter détruit");
  }
}

// Observer
function watchFiltersForm() {
  const observer = new MutationObserver(() => {
    const form = document.querySelector("#filters-form");
    if (form && !form.dataset.initialized) {
      new VehiclesFilter(form);
    }
  });
  observer.observe(document.body, { childList: true, subtree: true });
}

document.addEventListener("DOMContentLoaded", watchFiltersForm);
