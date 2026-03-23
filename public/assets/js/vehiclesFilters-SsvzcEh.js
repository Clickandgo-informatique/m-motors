// VehiclesFilter.js
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  /**
   * @param {HTMLElement} container Le parent du formulaire
   */
  constructor(container) {
    // Vérifie que le container est un élément DOM
    if (!(container instanceof HTMLElement)) {
      console.warn(
        "[VehiclesFilter] container invalide, utilisation document.body"
      );
      container = document.body;
    }

    this.container = container;
    console.log(
      "[VehiclesFilter] Initialisation avec container :",
      this.container
    );

    // Cherche le formulaire dans le container
    this.form = this.container.querySelector("[data-fetch-form]");
    console.log("[VehiclesFilter] Form trouvé :", this.form);

    if (!this.form) {
      console.warn("[VehiclesFilter] Aucun formulaire trouvé, arrêt.");
      return;
    }

    this.fetchUrl = this.form.dataset.fetchUrl;
    if (!this.fetchUrl) {
      console.warn("[VehiclesFilter] Aucun fetchUrl trouvé, arrêt.");
      return;
    }

    // Targets pour injection
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

    console.log("[VehiclesFilter] Initialisation sliders et events");
    this.initSliders();
    this.bindEvents();
  }

  /**
   * Initialise tous les sliders présents dans le container
   */
  initSliders() {
    this.container.querySelectorAll(".double-slider").forEach(slider => {
      console.log("[VehiclesFilter] Slider trouvé :", slider);
      initDoubleSlider(slider);

      slider.addEventListener("sliderChanged", e => {
        console.log("[VehiclesFilter] SliderChanged :", e.detail);
        slider.dataset.valueLow = e.detail.min;
        slider.dataset.valueHigh = e.detail.max;
        this.debounceSubmit();
      });
    });
  }

  /**
   * Bind les événements du formulaire : input/change, submit, pagination
   */
  bindEvents() {
    console.log("[VehiclesFilter] BindEvents attachés");

    // Soumission du formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      console.log("[VehiclesFilter] Form submit");
      this.debounceSubmit();
    });

    // Changement sur input / select
    this.container.addEventListener("change", e => {
      if (e.target.matches("input, select")) {
        console.log(
          "[VehiclesFilter] Input/Select changé :",
          e.target.name,
          e.target.value
        );
        this.debounceSubmit();
      }
    });

    // Pagination
    this.container.addEventListener("click", e => {
      const link = e.target.closest("[data-page]");
      if (link) {
        e.preventDefault();
        const page = parseInt(link.dataset.page);
        console.log("[VehiclesFilter] Pagination click, page :", page);
        if (!isNaN(page)) this.debounceSubmit(page);
      }
    });
  }

  /**
   * Debounce avant soumission AJAX
   */
  debounceSubmit(page = 1, delay = 200) {
    console.log("[VehiclesFilter] Debounce submit, page :", page);
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(page), delay);
  }

  /**
   * Soumission AJAX des filtres
   */
  async submitFilters(page = 1) {
    console.log("[VehiclesFilter] submitFilters page :", page);

    const formData = new FormData(this.form);
    const filters = {};

    // Transforme les checkbox / select en tableaux
    formData.forEach((val, key) => {
      const cleanKey = key.replace(/\[\]$/, "");
      if (filters[cleanKey]) {
        filters[cleanKey] = [].concat(filters[cleanKey], val);
      } else {
        filters[cleanKey] = [val]; // toujours un tableau
      }
    });

    // Ajoute les sliders
    this.container.querySelectorAll(".double-slider").forEach(slider => {
      const filterName = slider.dataset.filter; // ex: "mileage"
      const min = parseInt(slider.dataset.valueLow);
      const max = parseInt(slider.dataset.valueHigh);

      filters[`${filterName}Min`] = min;
      filters[`${filterName}Max`] = max;
    });

    console.log("[VehiclesFilter] Filters envoyés :", filters);

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
      console.log("[VehiclesFilter] Réponse AJAX :", data);

      // Injection dans le DOM
      if (this.resultsTarget) this.resultsTarget.innerHTML = data.results;
      if (this.paginationTopTarget)
        this.paginationTopTarget.innerHTML = data.paginationTop;
      if (this.paginationBottomTarget)
        this.paginationBottomTarget.innerHTML = data.paginationBottom;

      // Réinitialise les sliders après injection
      this.initSliders();

      // Scroll vers les résultats
      if (this.resultsTarget)
        this.resultsTarget.scrollIntoView({
          behavior: "smooth",
          block: "start"
        });
    } catch (e) {
      console.error("[VehiclesFilter] AJAX error :", e);
    }
  }
}

// Observer pour formulaire injecté dynamiquement
const observer = new MutationObserver(mutations => {
  mutations.forEach(mutation => {
    mutation.addedNodes.forEach(node => {
      if (node.nodeType === 1 && node.matches("[data-fetch-form]")) {
        console.log("[VehiclesFilter] Formulaire injecté détecté :", node);
        new VehiclesFilter(
          node.closest("[data-fetch-form-container]") || document.body
        );
      }
    });
  });
});

observer.observe(document.body, { childList: true, subtree: true });

// Initialisation si formulaire déjà présent
document.addEventListener("DOMContentLoaded", () => {
  console.log("[VehiclesFilter] DOMContentLoaded - initialisation");
  new VehiclesFilter(document.body);
});
