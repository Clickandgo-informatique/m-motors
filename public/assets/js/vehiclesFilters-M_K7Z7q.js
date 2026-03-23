// vehiclesFilters.js
import FilterBadges from "./FilterBadges.js";

/**
 * Gestion des filtres véhicules + pagination AJAX + badges + slider avec debounce
 */
export default class VehiclesFilter {
  constructor(form) {
    console.log("INIT VehiclesFilter → form reçu :", form);

    if (!(form instanceof HTMLFormElement)) {
      console.error("VehiclesFilter : élément invalide", form);
      return;
    }

    this.form = form;
    this.url = this.form.dataset.fetchUrl;
    if (!this.url) {
      console.error("VehiclesFilter : data-fetch-url manquant");
      return;
    }

    // Conteneurs
    this.resultsContainer = document.querySelector(
      '[data-target="vehicles-search-results"]'
    );
    this.paginationTop = document.querySelector(
      '[data-target="pagination-top"]'
    );
    this.paginationBottom = document.querySelector(
      '[data-target="pagination-bottom"]'
    );
    this.summaryContainer = document.querySelector(
      '[data-target="filters-summary"]'
    );

    console.log("Containers : ", {
      results: this.resultsContainer,
      top: this.paginationTop,
      bottom: this.paginationBottom,
      summary: this.summaryContainer
    });

    // Badges dynamiques
    if (this.summaryContainer) {
      this.badges = new FilterBadges(
        this.summaryContainer,
        this.form,
        this.submitFilters.bind(this)
      );
    }

    // Slider
    this.initSlider();

    // Événements
    this.initEvents();
  }

  initSlider() {
    const slider = this.form.querySelector(".double-slider");
    if (!slider || typeof initDoubleSlider !== "function") {
      console.warn("Slider non trouvé ou initDoubleSlider manquant");
      return;
    }

    console.log("Initialisation slider :", slider);

    // Debounce 300ms
    let timer = null;
    initDoubleSlider(slider);

    slider.addEventListener("sliderChanged", e => {
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

      clearTimeout(timer);
      timer = setTimeout(() => {
        console.log("Slider déclenche submitFilters :", { filter, min, max });
        this.submitFilters();
      }, 300);
    });
  }

  initEvents() {
    // Changement dans le formulaire → submitFilters
    this.form.addEventListener("change", e => {
      if (!e.target.matches("input")) return;
      console.log("Change détecté :", e.target);
      this.submitFilters();
    });

    // Pagination
    document.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;
      e.preventDefault();
      const page = Number.parseInt(btn.dataset.page);
      console.log("Pagination click → page :", page);
      if (!isNaN(page)) this.submitFilters(page);
    });

    // Badges → suppression d’un filtre
    if (this.summaryContainer) {
      this.summaryContainer.addEventListener("click", e => {
        if (!e.target.matches(".badge-remove")) return;
        const filter = e.target.dataset.filter;
        const value = e.target.dataset.value;
        console.log("Badge remove click :", { filter, value });

        // Checkbox multiples
        const checkboxes = this.form.querySelectorAll(
          `input[name="filters[${filter}][]"]`
        );
        checkboxes.forEach(cb => {
          if (cb.value === value) cb.checked = false;
        });

        // Slider mileage
        if (filter === "mileage") {
          const [min, max] = value.split("-");
          const inputMin = this.form.querySelector(
            `input[name="filters[mileageMin]"]`
          );
          const inputMax = this.form.querySelector(
            `input[name="filters[mileageMax]"]`
          );
          if (inputMin && inputMax) {
            inputMin.value = min;
            inputMax.value = max;
          }
        }

        this.submitFilters();
      });
    }
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
      } else filters[name] = value;
    }

    console.log("submitFilters → filters :", filters, "page :", page);

    try {
      const res = await fetch(this.url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters, page })
      });
      const data = await res.json();
      this.updateDOM(data, filters);
    } catch (err) {
      console.error("Erreur AJAX :", err);
    }
  }

  updateDOM(data, filters) {
    console.log("updateDOM → data :", data);

    if (this.resultsContainer && data.results) {
      console.log("Injection résultats dans ", this.resultsContainer);
      this.resultsContainer.innerHTML = data.results;
    } else console.warn("Container résultats non trouvé");

    if (this.paginationTop && data.paginationTop)
      this.paginationTop.innerHTML = data.paginationTop;
    if (this.paginationBottom && data.paginationBottom)
      this.paginationBottom.innerHTML = data.paginationBottom;

    if (this.summaryContainer && this.badges) {
      console.log("Mise à jour badges → filters :", filters);
      this.badges.render(filters);
    }
  }
}

/**
 * Observer pour détecter le formulaire dynamique
 */
function watchFiltersForm() {
  const observer = new MutationObserver(() => {
    const form = document.querySelector("#filters-form");
    if (!form || form.dataset.initialized) return;
    form.dataset.initialized = "true";
    console.log("Formulaire détecté → initialisation VehiclesFilter");
    new VehiclesFilter(form);
  });

  observer.observe(document.body, { childList: true, subtree: true });
}

document.addEventListener("DOMContentLoaded", watchFiltersForm);
