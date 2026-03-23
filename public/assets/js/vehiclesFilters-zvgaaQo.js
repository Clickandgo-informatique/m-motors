// vehiclesFilters.js
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  /**
   * @param {HTMLElement} container Conteneur où se trouve le formulaire de filtres
   */
  constructor(container) {
    if (!container || !(container instanceof HTMLElement)) {
      console.warn("VehiclesFilter : container invalide", container);
      return;
    }
    this.container = container;

    // Recherche du formulaire dans ce conteneur
    this.form = this.container.querySelector("[data-fetch-form]");
    if (!this.form) {
      console.warn("VehiclesFilter : formulaire introuvable dans le container");
      return;
    }

    // Cibles dynamiques
    this.resultsTarget = document.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = document.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = document.querySelector(
      "[data-target='pagination-bottom']"
    );
    this.summaryTarget = document.querySelector(
      "[data-target='filters-summary']"
    ); // résumé

    this.fetchUrl = this.form.dataset.fetchUrl;
    this.debounceTimeout = null;

    console.log("VehiclesFilter initialisé", {
      form: this.form,
      container: this.container
    });

    this.initSliders();
    this.bindEvents();
  }

  initSliders() {
    // Initialisation de tous les sliders double poignée
    this.form.querySelectorAll(".double-slider").forEach(slider => {
      initDoubleSlider(slider);

      slider.addEventListener("sliderChanged", e => {
        console.log("Slider changé", e.detail);
        // Mise à jour des inputs cachés pour le backend
        const filter = e.detail.filter;
        const minInput = this.form.querySelector(
          `input[name='filters[${filter}Min]']`
        );
        const maxInput = this.form.querySelector(
          `input[name='filters[${filter}Max]']`
        );
        if (minInput) minInput.value = e.detail.min;
        if (maxInput) maxInput.value = e.detail.max;

        this.debounceSubmit();
      });
    });
  }

  bindEvents() {
    // Soumission formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.debounceSubmit();
    });

    // Changement sur inputs / selects
    this.form.querySelectorAll("input[type='checkbox'], select").forEach(el => {
      el.addEventListener("change", () => {
        console.log("Filtre modifié", el.name, el.value);
        this.debounceSubmit();
      });
    });

    // Pagination click
    document.addEventListener("click", e => {
      const link = e.target.closest(
        ".pagination a[data-page], .pagination button[data-page]"
      );
      if (link) {
        e.preventDefault();
        const page = Number.parseInt(link.dataset.page);
        console.log("Pagination click page", page);
        this.debounceSubmit(page);
      }
    });
  }

  debounceSubmit(page = 1, delay = 200) {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(page), delay);
  }

  async submitFilters(page = 1) {
    if (!this.fetchUrl) {
      console.warn("VehiclesFilter : fetchUrl introuvable");
      return;
    }

    // Lecture des valeurs du formulaire
    const formData = new FormData(this.form);
    const filters = {};
    formData.forEach((val, key) => {
      // Gestion des tableaux
      if (key.endsWith("[]")) {
        const k = key.replace(/\[\]$/, "");
        if (!filters[k]) filters[k] = [];
        filters[k].push(val);
      } else {
        filters[key] = val;
      }
    });

    console.log("Envoi des filtres AJAX", { filters, page });

    try {
      const res = await fetch(`${this.fetchUrl}?page=${page}`, {
        method: "POST",
        body: JSON.stringify({ filters, q: filters.q || null, page }),
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      const data = await res.json();

      // Injection dans les cibles
      if (this.resultsTarget) this.resultsTarget.innerHTML = data.results;
      if (this.paginationTopTarget)
        this.paginationTopTarget.innerHTML = data.paginationTop;
      if (this.paginationBottomTarget)
        this.paginationBottomTarget.innerHTML = data.paginationBottom;

      // Mise à jour du résumé
      if (this.summaryTarget)
        this.summaryTarget.innerHTML = data.filtersSummary || "";

      console.log("AJAX terminé", data);
    } catch (e) {
      console.error("Erreur AJAX VehiclesFilter", e);
    }
  }
}
