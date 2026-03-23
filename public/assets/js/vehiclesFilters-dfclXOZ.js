// VehiclesFilter-debug.js
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilterDebug {
  constructor(container) {
    if (!(container instanceof HTMLElement)) {
      console.warn(
        "[VehiclesFilterDebug] container invalide, utilisation document.body"
      );
      container = document.body;
    }
    this.container = container;
    this.debounceTimeout = null;

    // Attente du formulaire (utile pour fragment chargé dynamiquement)
    this.waitForForm();
  }

  waitForForm(retries = 15, delay = 200) {
    this.form = this.container.querySelector("[data-fetch-form]");
    if (this.form) {
      console.log("[VehiclesFilterDebug] Formulaire trouvé");
      this.initForm();
    } else if (retries > 0) {
      console.log(
        `[VehiclesFilterDebug] Formulaire non trouvé, réessai dans ${delay}ms`
      );
      setTimeout(() => this.waitForForm(retries - 1, delay), delay);
    } else {
      console.error(
        "[VehiclesFilterDebug] Formulaire introuvable après plusieurs tentatives"
      );
    }
  }

  initForm() {
    this.fetchUrl = this.form.dataset.fetchUrl;
    if (!this.fetchUrl) {
      console.warn("[VehiclesFilterDebug] fetchUrl manquant sur le formulaire");
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

    // Sliders
    this.container.querySelectorAll(".double-slider").forEach(slider => {
      initDoubleSlider(slider);
      slider.addEventListener("sliderChanged", e => {
        console.log("[VehiclesFilterDebug] Slider modifié:", e.detail);
        slider.dataset.valueLow = e.detail.min;
        slider.dataset.valueHigh = e.detail.max;
        this.debounceSubmit();
      });
    });

    this.bindEvents();
    console.log("[VehiclesFilterDebug] Formulaire initialisé correctement");
  }

  bindEvents() {
    // Soumission formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      console.log("[VehiclesFilterDebug] Formulaire soumis");
      this.debounceSubmit();
    });

    // Change sur inputs/selects
    this.container.addEventListener("change", e => {
      if (e.target.matches("input, select")) {
        console.log(
          "[VehiclesFilterDebug] Changement détecté:",
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
          console.log("[VehiclesFilterDebug] Pagination click, page:", page);
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

    // Récupération des valeurs des checkboxes, selects et inputs
    formData.forEach((val, key) => {
      const cleanKey = key.replace(/\[\]$/, "");
      if (filters[cleanKey])
        filters[cleanKey] = [].concat(filters[cleanKey], val);
      else filters[cleanKey] = [val];
    });

    // Ajout des sliders
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

    // --- Debug complet des filtres envoyés ---
    console.group("[VehiclesFilterDebug] Préparation de l'envoi AJAX");
    console.log("Page :", page);
    console.log("Filtres envoyés :", filters);
    console.groupEnd();

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

      console.group("[VehiclesFilterDebug] Réponse AJAX reçue");
      console.log("Résultats HTML :", data.results);
      console.log("Pagination Top :", data.paginationTop);
      console.log("Pagination Bottom :", data.paginationBottom);
      console.groupEnd();

      if (this.resultsTarget) this.resultsTarget.innerHTML = data.results;
      if (this.paginationTopTarget)
        this.paginationTopTarget.innerHTML = data.paginationTop;
      if (this.paginationBottomTarget)
        this.paginationBottomTarget.innerHTML = data.paginationBottom;
    } catch (e) {
      console.error("[VehiclesFilterDebug] AJAX error :", e);
    }
  }
}

// --- Initialisation automatique pour sidebar déjà présente ---
document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.querySelector("#sidebar");
  if (sidebar) new VehiclesFilterDebug(sidebar);
});

// --- Pour fragment injecté dynamiquement ---
// new VehiclesFilterDebug(document.querySelector("#sidebar"));
