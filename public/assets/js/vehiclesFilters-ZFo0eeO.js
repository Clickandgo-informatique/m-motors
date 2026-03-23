/**
 * Module pour gérer les filtres véhicules et la pagination AJAX
 */
export default class VehiclesFilter {
  constructor(container = document) {
    if (!container) {
      console.warn("VehiclesFilter : container introuvable");
      return;
    }
    this.container = container;

    this.form = this.container.querySelector("#filters-form");
    if (!this.form) {
      console.warn("VehiclesFilter : formulaire #filters-form introuvable");
      return;
    }

    this.inputs = this.form.querySelectorAll("input");
    this.url = this.form.dataset.fetchUrl;
    if (!this.url) {
      console.warn("VehiclesFilter : data-fetch-url introuvable");
      return;
    }

    this.initSlider();
    this.initEvents();
  }

  initSlider() {
    const slider = this.form.querySelector(".double-slider");
    if (!slider) return;

    if (typeof initDoubleSlider === "function") {
      initDoubleSlider(slider);

      document.addEventListener("sliderChanged", e => {
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
      });
    }
  }

  initEvents() {
    this.inputs.forEach(input => {
      input.addEventListener("change", () => this.submitFilters());
    });

    document.addEventListener("click", e => {
      if (!e.target.dataset.page) return;
      e.preventDefault();
      const page = parseInt(e.target.dataset.page);
      if (!isNaN(page)) this.submitFilters(page);
    });
  }

  async submitFilters(page = 1) {
    const formData = new FormData(this.form);
    const filters = {};
    for (const [key, value] of formData.entries()) {
      if (key.endsWith("[]")) {
        const cleanKey = key.replace(/\[\]$/, "");
        filters[cleanKey] = filters[cleanKey] || [];
        filters[cleanKey].push(value);
      } else {
        filters[key] = value;
      }
    }

    const payload = { filters, q: null, page };

    try {
      const res = await fetch(this.url, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      this.updateResults(data, filters);
    } catch (err) {
      console.error("VehiclesFilter : erreur AJAX", err);
    }
  }

  updateResults(data, filters) {
    const resultsContainer = document.querySelector("#vehicles-results");
    if (resultsContainer && data.results)
      resultsContainer.innerHTML = data.results;

    const paginationTop = document.querySelector(".pagination-wrapper.top");
    if (paginationTop && data.paginationTop)
      paginationTop.innerHTML = data.paginationTop;

    const paginationBottom = document.querySelector(
      ".pagination-wrapper.bottom"
    );
    if (paginationBottom && data.paginationBottom)
      paginationBottom.innerHTML = data.paginationBottom;

    // Résumé des filtres
    const summaryContainer = document.querySelector(
      '[data-target="filters-summary"]'
    );
    if (summaryContainer) {
      fetch("/vehicles/filters-summary", {
        // Endpoint Symfony à créer
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters })
      })
        .then(r => r.text())
        .then(html => (summaryContainer.innerHTML = html))
        .catch(e => console.error("Erreur filters-summary", e));
    }
  }
}

document.addEventListener("DOMContentLoaded", () => {
  new VehiclesFilter(document);
});
