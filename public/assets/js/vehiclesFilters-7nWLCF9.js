// vehiclesFilters.js
import FilterBadges from "./FilterBadges.js";
import initDoubleSlider from "./rangeSelector.js"; // Import du module slider

/**
 * Classe principale de gestion des filtres véhicules
 * - Gère les sliders (mileage, year…)
 * - Gère les badges côté client
 * - Gère la pagination AJAX
 * - Déclenche submitFilters avec debounce
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

    // Récupération des containers du DOM
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

    console.log("Containers détectés :", {
      results: this.resultsContainer,
      top: this.paginationTop,
      bottom: this.paginationBottom,
      summary: this.summaryContainer
    });

    // Initialisation des badges côté client si container existant
    if (this.summaryContainer) {
      this.badges = new FilterBadges(
        this.summaryContainer,
        this.form,
        this.submitFilters.bind(this)
      );
    }

    // Initialisation des sliders
    this.initSliders();

    // Initialisation des événements (checkbox, pagination, badges)
    this.initEvents();
  }

  /**
   * Initialisation de tous les sliders présents dans le formulaire
   */
  initSliders() {
    const sliders = this.form.querySelectorAll(".double-slider");

    if (!sliders.length || typeof initDoubleSlider !== "function") {
      console.warn("Sliders non trouvés ou initDoubleSlider manquant");
      return;
    }

    sliders.forEach(slider => {
      console.log("Initialisation slider :", slider);

      let timer = null;
      initDoubleSlider(slider);

      // Écoute de l'événement custom sliderChanged
      slider.addEventListener("sliderChanged", e => {
        const { filter, min, max } = e.detail;

        // Synchronisation des hidden inputs du formulaire
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

        // Debounce 300ms avant de relancer le filtrage AJAX
        clearTimeout(timer);
        timer = setTimeout(() => {
          console.log("Slider déclenche submitFilters :", { filter, min, max });
          this.submitFilters();
        }, 300);
      });
    });
  }

  /**
   * Initialisation des événements
   * - Change des inputs/checkbox
   * - Click pagination
   * - Suppression badges
   */
  initEvents() {
    // Changement de valeur dans le formulaire
    this.form.addEventListener("change", e => {
      if (!e.target.matches("input")) return;
      console.log("Change détecté :", e.target);
      this.submitFilters();
    });

    // Pagination click
    document.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;
      e.preventDefault();
      const page = Number.parseInt(btn.dataset.page);
      console.log("Pagination click → page :", page);
      if (!isNaN(page)) this.submitFilters(page);
    });

    // Suppression d’un filtre via badge
    if (this.summaryContainer) {
      this.summaryContainer.addEventListener("click", e => {
        if (!e.target.matches(".badge-remove")) return;

        const filter = e.target.dataset.filter;
        const value = e.target.dataset.value;
        console.log("Badge remove click :", { filter, value });

        // Décocher la checkbox correspondante
        const checkboxes = this.form.querySelectorAll(
          `input[name="filters[${filter}][]"]`
        );
        checkboxes.forEach(cb => {
          if (cb.value === value) cb.checked = false;
        });

        // Si filtre slider (mileage ou year)
        const sliderInputs = ["Min", "Max"].map(suf =>
          this.form.querySelector(`input[name="filters[${filter}${suf}]"]`)
        );
        if (sliderInputs[0] && sliderInputs[1] && value.includes("-")) {
          const [min, max] = value.split("-");
          sliderInputs[0].value = min;
          sliderInputs[1].value = max;
        }

        // Met à jour les badges côté client
        if (this.badges) this.badges.updateBadges();

        // Relance filtrage AJAX
        this.submitFilters();
      });
    }
  }

  /**
   * Envoi des filtres via AJAX
   * @param {number} page
   */
  async submitFilters(page = 1) {
    const formData = new FormData(this.form);
    const filters = {};

    // Transformation FormData en objet JS
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
      this.updateDOM(data);
    } catch (err) {
      console.error("Erreur AJAX :", err);
    }
  }

  /**
   * Mise à jour du DOM après réception des résultats AJAX
   * @param {Object} data
   */
  updateDOM(data) {
    console.log("updateDOM → data :", data);

    // Résultats
    if (this.resultsContainer && data.results) {
      console.log("Injection résultats dans ", this.resultsContainer);
      this.resultsContainer.innerHTML = data.results;
    } else console.warn("Container résultats non trouvé");

    // Pagination
    if (this.paginationTop && data.paginationTop)
      this.paginationTop.innerHTML = data.paginationTop;
    if (this.paginationBottom && data.paginationBottom)
      this.paginationBottom.innerHTML = data.paginationBottom;

    // Mise à jour badges côté client
    if (this.badges) {
      console.log("Mise à jour des badges côté client");
      this.badges.updateBadges();
    }
  }
}

/**
 * Observer pour initialiser automatiquement le formulaire si chargé dynamiquement
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

// Initialisation après chargement du DOM
document.addEventListener("DOMContentLoaded", watchFiltersForm);
