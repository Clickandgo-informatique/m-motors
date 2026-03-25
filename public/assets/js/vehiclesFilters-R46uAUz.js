// vehiclesFilters.js
import FilterBadges from "./FilterBadges.js";
import initDoubleSlider from "./rangeSelector.js";

/**
 * Gestion des filtres véhicules :
 * - Filtrage AJAX
 * - Pagination
 * - Badges interactifs
 * - Double sliders avec debounce
 */
export default class VehiclesFilter {
  constructor(form) {
    // Vérification que l'élément est bien un formulaire
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.url = form.dataset.fetchUrl;
    if (!this.url) return;

    // Conteneurs principaux dans la page
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

    // Initialisation des badges côté client si conteneur présent
    if (this.summaryContainer) {
      this.badges = new FilterBadges(
        this.summaryContainer,
        this.form,
        this.submitFilters.bind(this)
      );
    }

    // Initialisation des sliders (kilométrage, années, etc.)
    this.initSliders();

    // Initialisation des événements (inputs, pagination, badges)
    this.initEvents();
  }

  /**
   * Initialise tous les sliders présents dans le formulaire
   */
  initSliders() {
    const sliders = this.form.querySelectorAll(".double-slider");
    if (!sliders.length || typeof initDoubleSlider !== "function") return;

    sliders.forEach(slider => {
      // Initialisation du slider via la fonction importée
      initDoubleSlider(slider);

      // Débounce pour éviter trop de requêtes AJAX lors du glissement du slider
      let timer = null;
      slider.addEventListener("sliderChanged", e => {
        const { filter, min, max } = e.detail;

        // Mise à jour des inputs cachés associés
        const inputMin = this.form.querySelector(
          `input[name="filters[${filter}Min]"]`
        );
        const inputMax = this.form.querySelector(
          `input[name="filters[${filter}Max]"]`
        );
        if (inputMin) inputMin.value = min;
        if (inputMax) inputMax.value = max;

        // Déclenchement du filtrage AJAX après 300ms
        clearTimeout(timer);
        timer = setTimeout(() => this.submitFilters(), 300);
      });
    });
  }

  /**
   * Initialisation des événements :
   * - Changement de checkbox/input
   * - Pagination
   * - Clic sur les badges pour suppression d'un filtre
   */
  initEvents() {
    // Détection des changements sur les inputs du formulaire
    this.form.addEventListener("change", e => {
      if (!e.target.matches("input")) return;
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

    // Gestion du clic sur les badges
    if (this.summaryContainer) {
      this.summaryContainer.addEventListener("click", e => {
        if (!e.target.matches(".badge-remove")) return;

        const filter = e.target.dataset.filter;
        const value = e.target.dataset.value;

        // Si badge lié à un slider (range), reset du slider
        const slider = this.form.querySelector(
          `.double-slider[data-filter="${filter}"]`
        );
        if (slider && typeof slider.resetSlider === "function") {
          slider.resetSlider();
        } else {
          // Sinon, checkbox classique : décocher la valeur correspondante
          const checkboxes = this.form.querySelectorAll(
            `input[name="filters[${filter}][]"]`
          );
          checkboxes.forEach(cb => {
            if (cb.value === value) cb.checked = false;
          });
        }

        // Mise à jour visuelle des badges
        if (this.badges) this.badges.updateBadges();

        // Relance du filtrage AJAX
        this.submitFilters();
      });
    }
  }

  /**
   * Soumission AJAX des filtres
   * @param {number} page - Numéro de page pour pagination
   */
  async submitFilters(page = 1) {
    const formData = new FormData(this.form);
    const filters = {};

    // Construction de l'objet filters à partir des inputs
    for (const [key, value] of formData.entries()) {
      const match = key.match(/^filters\[(.+?)\](\[\])?$/);
      if (!match) continue;
      const name = match[1];
      const isArray = !!match[2];
      if (isArray) {
        if (!filters[name]) filters[name] = [];
        filters[name].push(value);
      } else {
        filters[name] = value;
      }
    }

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
   * Mise à jour du DOM avec les résultats et pagination
   */
  updateDOM(data) {
    if (this.resultsContainer && data.results)
      this.resultsContainer.innerHTML = data.results;
    if (this.paginationTop && data.paginationTop)
      this.paginationTop.innerHTML = data.paginationTop;
    if (this.paginationBottom && data.paginationBottom)
      this.paginationBottom.innerHTML = data.paginationBottom;

    // Mise à jour des badges après injection des résultats
    if (this.badges) this.badges.updateBadges();
  }
}

/**
 * Observer pour détecter un formulaire ajouté dynamiquement
 */
function watchFiltersForm() {
  const observer = new MutationObserver(() => {
    const form = document.querySelector("#filters-form");
    if (!form || form.dataset.initialized) return;
    form.dataset.initialized = "true";
    new VehiclesFilter(form);
  });

  observer.observe(document.body, { childList: true, subtree: true });
}

// Initialisation au chargement de la page
document.addEventListener("DOMContentLoaded", watchFiltersForm);
