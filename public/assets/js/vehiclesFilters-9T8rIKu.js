import FilterBadges from "./FilterBadges.js";
import initDoubleSlider from "./rangeSelector.js";
import Autocomplete from "./Autocomplete.js";

export default class VehiclesFilter {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.url = form.dataset.fetchUrl;
    if (!this.url) return;

    // Container principal des véhicules (sidebar + view)
    this.container = document.querySelector("#vehicles-container");
    this.resultsEl = this.container?.querySelector("#vehicles-search-results"); // seulement le grid/table, PAS l'autocomplete
    this.paginationTop = document.querySelector(
      '[data-target="pagination-top"]'
    );
    this.paginationBottom = document.querySelector(
      '[data-target="pagination-bottom"]'
    );
    this.summaryContainer = document.querySelector(
      '[data-target="filters-summary"]'
    );

    if (!this.container || !this.resultsEl) {
      console.warn(
        "VehiclesFilter : container de résultats introuvable",
        this.container
      );
      return;
    }

    // Badges (si sidebar filters)
    if (this.summaryContainer && this.form.matches("#filters-form")) {
      this.badges = new FilterBadges(
        this.summaryContainer,
        this.form,
        this.submitFilters.bind(this)
      );
    }

    if (this.form.matches("#filters-form")) this.initSliders();
    this.initEvents();

    // Autocomplete indépendant : ne touche pas à resultsEl
    this.initAutocomplete();
  }

  initSliders() {
    const sliders = this.form.querySelectorAll(".double-slider");
    if (!sliders.length || typeof initDoubleSlider !== "function") return;

    sliders.forEach(slider => {
      initDoubleSlider(slider);
      let timer = null;

      slider.addEventListener("sliderChanged", e => {
        const { filter, min, max } = e.detail;
        const inputMin = this.form.querySelector(
          `input[name="filters[${filter}Min]"]`
        );
        const inputMax = this.form.querySelector(
          `input[name="filters[${filter}Max]"]`
        );

        if (inputMin) inputMin.value = min;
        if (inputMax) inputMax.value = max;

        clearTimeout(timer);
        timer = setTimeout(() => this.submitFilters(), 300);
      });
    });
  }

  initEvents() {
    // Changement sur filtres ou toggle view
    this.form.addEventListener("change", e => {
      if (!e.target.matches("input, select")) return;
      this.submitFilters();
    });

    // Pagination
    document.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;
      e.preventDefault();
      const page = Number.parseInt(btn.dataset.page);
      if (!isNaN(page)) this.submitFilters(page);
    });

    // Suppression badges
    if (this.badges) {
      this.summaryContainer.addEventListener("click", e => {
        if (!e.target.matches(".badge-remove")) return;
        const filter = e.target.dataset.filter;
        const value = e.target.dataset.value;

        // Reset slider si applicable
        const slider = this.form.querySelector(
          `.double-slider[data-filter="${filter}"]`
        );
        if (slider && typeof slider.resetSlider === "function")
          slider.resetSlider();
        else {
          const checkboxes = this.form.querySelectorAll(
            `input[name="filters[${filter}][]"]`
          );
          checkboxes.forEach(cb => {
            if (cb.value === value) cb.checked = false;
          });
        }

        this.badges.updateBadges();
        this.submitFilters();
      });
    }
  }

  async submitFilters(page = 1) {
    try {
      const formData = new FormData(this.form);
      const filters = {};
      for (const [key, value] of formData.entries()) {
        const match = key.match(/^filters\[(.+?)\](\[\])?$/);
        if (!match) continue;
        const name = match[1];
        const isArray = !!match[2];
        if (isArray) {
          filters[name] = filters[name] || [];
          filters[name].push(value);
        } else filters[name] = value;
      }

      const viewInput = this.form.querySelector("input[name='view']:checked");
      if (viewInput) filters.view = viewInput.value;

      const res = await fetch(this.url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters, page })
      });

      const data = await res.json();

      // Injection **uniquement** dans le container principal
      if (this.resultsEl && data.results)
        this.resultsEl.innerHTML = data.results;
      if (this.paginationTop && data.paginationTop)
        this.paginationTop.innerHTML = data.paginationTop;
      if (this.paginationBottom && data.paginationBottom)
        this.paginationBottom.innerHTML = data.paginationBottom;
      if (this.badges) this.badges.updateBadges();
    } catch (err) {
      console.error("Erreur AJAX :", err);
    }
  }

  initAutocomplete() {
    // Tous les inputs avec autocomplete, indépendamment du container principal
    this.form.querySelectorAll("[data-autocomplete]").forEach(input => {
      if (!input.dataset.autocompleteInitialized) {
        new Autocomplete(input);
        input.dataset.autocompleteInitialized = "true";
      }
    });
  }
}
