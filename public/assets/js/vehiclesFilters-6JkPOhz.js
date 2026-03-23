// VehiclesFilter.js avec logs
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(container = document.body) {
    console.log("[VehiclesFilter] Initialisation avec container :", container);

    this.container = container;

    // Cherche le formulaire dans le container
    this.form = this.container.querySelector("[data-fetch-form]");
    console.log("[VehiclesFilter] Form trouvé :", this.form);

    if (!this.form) {
      console.warn(
        "[VehiclesFilter] Aucun formulaire trouvé, arrêt de l'initialisation."
      );
      return;
    }

    this.fetchUrl = this.form.dataset.fetchUrl;
    console.log("[VehiclesFilter] fetchUrl :", this.fetchUrl);

    if (!this.fetchUrl) {
      console.warn(
        "[VehiclesFilter] Aucun fetchUrl trouvé, arrêt de l'initialisation."
      );
      return;
    }

    // Cibles pour injection AJAX
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

    console.log("[VehiclesFilter] Initialisation sliders et événements");
    this.initSliders();
    this.bindEvents();
  }

  initSliders() {
    console.log("[VehiclesFilter] Initialisation sliders");
    this.container.querySelectorAll(".double-slider").forEach(slider => {
      console.log("[VehiclesFilter] Slider trouvé :", slider);
      initDoubleSlider(slider);

      slider.addEventListener("sliderChanged", e => {
        console.log("[VehiclesFilter] SliderChanged :", e.detail);
        const filterName = slider.dataset.filter;
        slider.dataset.valueLow = e.detail.min;
        slider.dataset.valueHigh = e.detail.max;
        this.debounceSubmit();
      });
    });
  }

  bindEvents() {
    console.log("[VehiclesFilter] BindEvents attachés");

    // Soumission formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      console.log("[VehiclesFilter] Formulaire soumis");
      this.debounceSubmit();
    });

    // Changement input/select
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

    // Pagination via delegation
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

  debounceSubmit(page = 1, delay = 200) {
    console.log("[VehiclesFilter] Debounce submit, page :", page);
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(page), delay);
  }

  async submitFilters(page = 1) {
    console.log("[VehiclesFilter] submitFilters page :", page);
    if (!this.fetchUrl) {
      console.warn("[VehiclesFilter] submitFilters annulé, fetchUrl manquant");
      return;
    }

    const formData = new FormData(this.form);
    const filters = {};

    formData.forEach((val, key) => {
      key = key.replace(/\[\]$/, "");
      if (filters[key]) {
        filters[key] = [].concat(filters[key], val);
      } else {
        filters[key] = val;
      }
    });

    this.container.querySelectorAll(".double-slider").forEach(slider => {
      const filterName = slider.dataset.filter;
      filters[`${filterName}Min`] = parseInt(slider.dataset.valueLow);
      filters[`${filterName}Max`] = parseInt(slider.dataset.valueHigh);
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
      console.log("[VehiclesFilter] Réponse AJAX reçue :", data);

      if (this.resultsTarget) {
        this.resultsTarget.innerHTML = data.results;
        console.log("[VehiclesFilter] Injection résultats");
      }
      if (this.paginationTopTarget) {
        this.paginationTopTarget.innerHTML = data.paginationTop;
        console.log("[VehiclesFilter] Injection paginationTop");
      }
      if (this.paginationBottomTarget) {
        this.paginationBottomTarget.innerHTML = data.paginationBottom;
        console.log("[VehiclesFilter] Injection paginationBottom");
      }

      this.initSliders();

      if (this.resultsTarget) {
        this.resultsTarget.scrollIntoView({
          behavior: "smooth",
          block: "start"
        });
        console.log("[VehiclesFilter] Scroll vers résultats");
      }
    } catch (e) {
      console.error("[VehiclesFilter] AJAX error :", e);
    }
  }
}

// Observer pour formulaire injecté dynamiquement
const observer = new MutationObserver(mutations => {
  mutations.forEach(mutation => {
    mutation.addedNodes.forEach(node => {
      if (
        node.nodeType === 1 &&
        node.matches &&
        node.matches("[data-fetch-form]")
      ) {
        console.log("[VehiclesFilter] Formulaire injecté détecté :", node);
        new VehiclesFilter(
          node.closest("[data-fetch-form-container]") || document.body
        );
      }
    });
  });
});

observer.observe(document.body, { childList: true, subtree: true });

document.addEventListener("DOMContentLoaded", () => {
  console.log("[VehiclesFilter] DOMContentLoaded - initialisation");
  new VehiclesFilter(document.body);
});
