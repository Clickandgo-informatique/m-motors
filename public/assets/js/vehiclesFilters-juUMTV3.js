// VehiclesFilter.js
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(container) {
    if (!(container instanceof HTMLElement)) {
      console.warn(
        "[VehiclesFilter] container invalide, utilisation document.body"
      );
      container = document.body;
    }
    this.container = container;
    this.debounceTimeout = null;

    // Essaye d'initialiser le formulaire maintenant ou après un court délai si fragment injecté
    this.waitForForm();
  }

  waitForForm(retries = 10, delay = 200) {
    this.form = this.container.querySelector("[data-fetch-form]");
    if (this.form) {
      console.log("[VehiclesFilter] Formulaire trouvé");
      this.initForm();
    } else if (retries > 0) {
      console.log(
        "[VehiclesFilter] Formulaire non trouvé, réessai dans",
        delay,
        "ms"
      );
      setTimeout(() => this.waitForForm(retries - 1, delay), delay);
    } else {
      console.warn(
        "[VehiclesFilter] Formulaire introuvable après plusieurs tentatives"
      );
    }
  }

  initForm() {
    this.fetchUrl = this.form.dataset.fetchUrl;
    if (!this.fetchUrl) {
      console.warn("[VehiclesFilter] fetchUrl manquant sur le formulaire");
      return;
    }

    // Targets pour injection AJAX
    this.resultsTarget = this.container.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = this.container.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = this.container.querySelector(
      "[data-target='pagination-bottom']"
    );

    // Initialisation sliders
    this.container.querySelectorAll(".double-slider").forEach(slider => {
      initDoubleSlider(slider);
      slider.addEventListener("sliderChanged", e => {
        console.log("[VehiclesFilter] Slider modifié:", e.detail);
        // Met à jour les dataset du slider pour envoyer min/max corrects
        slider.dataset.valueLow = e.detail.min;
        slider.dataset.valueHigh = e.detail.max;
        this.debounceSubmit();
      });
    });

    // Événements sur le formulaire et la pagination
    this.bindEvents();

    console.log("[VehiclesFilter] Formulaire initialisé correctement");
  }

  bindEvents() {
    // Soumission formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.debounceSubmit();
    });

    // Changement sur inputs / selects (checkboxes incluses)
    this.container.addEventListener("change", e => {
      if (e.target.matches("input, select")) {
        console.log(
          "[VehiclesFilter] Changement détecté sur:",
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
        if (!isNaN(page)) {
          console.log("[VehiclesFilter] Pagination click, page:", page);
          this.debounceSubmit(page);
        }
      }
    });
  }

  debounceSubmit(page = 1, delay = 200) {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(page), delay);
  }

  async submitFilters(page = 1) {
    if (!this.form || !this.fetchUrl) return;

    const formData = new FormData(this.form);
    const filters = {};

    // Récupère toutes les valeurs checkbox / select / input
    formData.forEach((val, key) => {
      const cleanKey = key.replace(/\[\]$/, "");
      if (filters[cleanKey])
        filters[cleanKey] = [].concat(filters[cleanKey], val);
      else filters[cleanKey] = [val];
    });

    // Récupère les valeurs min/max des sliders
    this.container.querySelectorAll(".double-slider").forEach(slider => {
      const filterName = slider.dataset.filter;
      if (
        slider.dataset.valueLow !== undefined &&
        slider.dataset.valueHigh !== undefined
      ) {
        filters[`${filterName}Min`] = parseInt(slider.dataset.valueLow);
        filters[`${filterName}Max`] = parseInt(slider.dataset.valueHigh);
      }
    });

    console.log(
      "[VehiclesFilter] Envoi AJAX avec filtres:",
      filters,
      "page:",
      page
    );

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

      console.log("[VehiclesFilter] Résultats injectés avec succès");
    } catch (e) {
      console.error("[VehiclesFilter] AJAX error :", e);
    }
  }
}

// --- Initialisation automatique pour sidebar déjà présente ---
document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.querySelector("#sidebar");
  if (sidebar) new VehiclesFilter(sidebar);
});

// --- Pour fragment injecté dynamiquement ---
// new VehiclesFilter(document.querySelector("#sidebar"));
