// VehiclesFilter.js
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(formSelector = "#filters-form") {
    this.form = document.querySelector(formSelector);
    if (!this.form) return;

    // Conteneur principal du formulaire
    this.container = this.form.closest("[data-fetch-form]");
    if (!this.container) return;

    // Targets dynamiques
    this.resultsTarget = document.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = document.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = document.querySelector(
      "[data-target='pagination-bottom']"
    );

    this.fetchUrl = this.form.dataset.fetchUrl;
    this.debounceTimeout = null;

    this.initSliders();
    this.bindEvents();
  }

  initSliders() {
    // Initialisation des doublesliders présents dans le formulaire
    this.form.querySelectorAll(".double-slider").forEach(slider => {
      initDoubleSlider(slider);

      // Écoute des changements pour soumettre le filtre
      slider.addEventListener("sliderChanged", () => this.debounceSubmit());
    });
  }

  bindEvents() {
    // Soumission du formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.debounceSubmit();
    });

    // Changement sur inputs / select
    this.form.querySelectorAll("input, select").forEach(el => {
      el.addEventListener("change", () => this.debounceSubmit());
    });

    // Pagination click
    document.addEventListener("click", e => {
      const link = e.target.closest(".pagination a[data-page]");
      if (link) {
        e.preventDefault();
        const page = Number.parseInt(link.dataset.page);
        this.debounceSubmit(page);
      }
    });

    // Scroll après injection
    this.scrollToResults = () => {
      if (this.resultsTarget) {
        this.resultsTarget.scrollIntoView({
          behavior: "smooth",
          block: "start"
        });
      }
    };
  }

  debounceSubmit(page = 1, delay = 150) {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(page), delay);
  }

  async submitFilters(page = 1) {
    if (!this.fetchUrl) return;

    // Récupération des valeurs du formulaire
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

      // Injection dans les targets
      if (this.resultsTarget) {
        this.resultsTarget.innerHTML = data.results;
        console.log("div de résultats détectée");
      }
      if (this.paginationTopTarget) {
        this.paginationTopTarget.innerHTML = data.paginationTop;
        console.log("div de pagination haute détectée");
      }
      if (this.paginationBottomTarget) {
        this.paginationBottomTarget.innerHTML = data.paginationBottom;
        console.log("div pagination basse détectée");
      }

      // Scroll vers les résultats
      this.scrollToResults();
    } catch (e) {
      console.error("VehiclesFilter AJAX error", e);
    }
  }
}
