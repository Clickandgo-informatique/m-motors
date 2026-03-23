// VehiclesFilter.js
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor() {
    // On tente de récupérer le formulaire avec l'attribut data-fetch-form
    // Cela fonctionne même si le data-fetch-form est sur le <form> directement
    this.form = document.querySelector("[data-fetch-form]");
    if (!this.form) return; // Stop si formulaire introuvable

    // Container parent du formulaire (utile pour délégation)
    // Si pas de container spécifique, on prend document.body
    this.container =
      this.form.closest("[data-fetch-form-container]") || document.body;

    // URL de l'endpoint AJAX
    this.fetchUrl = this.form.dataset.fetchUrl;
    if (!this.fetchUrl) return; // Stop si pas d'URL

    // Cibles pour injection des résultats et pagination
    this.resultsTarget = this.container.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = this.container.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = this.container.querySelector(
      "[data-target='pagination-bottom']"
    );

    // Timeout pour debounce
    this.debounceTimeout = null;

    // Initialisation des sliders déjà présents
    this.initSliders();

    // Bind des événements sur formulaire et pagination
    this.bindEvents();
  }

  /**
   * Initialise tous les sliders (double-slider) du formulaire
   * et écoute leur événement "sliderChanged" pour déclencher la recherche
   */
  initSliders() {
    this.container.querySelectorAll(".double-slider").forEach(slider => {
      initDoubleSlider(slider);

      // Déclenchement de la recherche à chaque changement du slider
      slider.addEventListener("sliderChanged", () => this.debounceSubmit());
    });
  }

  /**
   * Bind des événements :
   * - soumission du formulaire
   * - changement d'input ou select
   * - clic sur la pagination
   */
  bindEvents() {
    // Soumission du formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.debounceSubmit();
    });

    // Changement sur inputs ou selects (checkbox, select, text)
    this.container.addEventListener("change", e => {
      if (e.target.matches("input, select")) {
        this.debounceSubmit();
      }
    });

    // Pagination via delegation
    this.container.addEventListener("click", e => {
      const link = e.target.closest("[data-page]");
      if (link) {
        e.preventDefault();
        const page = parseInt(link.dataset.page);
        if (!isNaN(page)) {
          this.debounceSubmit(page);
        }
      }
    });
  }

  /**
   * Debounce pour limiter les appels AJAX lors des changements rapides
   */
  debounceSubmit(page = 1, delay = 200) {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(() => this.submitFilters(page), delay);
  }

  /**
   * Envoie la requête AJAX pour filtrer les véhicules
   * @param {number} page - numéro de page à récupérer
   */
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
      // Envoi AJAX
      const res = await fetch(`${this.fetchUrl}?page=${page}`, {
        method: "POST",
        body: JSON.stringify({ filters, q: filters.q || null }),
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      const data = await res.json();

      // Injection des résultats
      if (this.resultsTarget) this.resultsTarget.innerHTML = data.results;

      // Injection de la pagination
      if (this.paginationTopTarget)
        this.paginationTopTarget.innerHTML = data.paginationTop;
      if (this.paginationBottomTarget)
        this.paginationBottomTarget.innerHTML = data.paginationBottom;

      // Réinitialisation des sliders après injection AJAX
      this.initSliders();

      // Scroll vers les résultats
      if (this.resultsTarget) {
        this.resultsTarget.scrollIntoView({
          behavior: "smooth",
          block: "start"
        });
      }
    } catch (e) {
      console.error("VehiclesFilter AJAX error", e);
    }
  }
}

// Initialisation après que le DOM soit complètement chargé
document.addEventListener("DOMContentLoaded", () => {
  new VehiclesFilter();
});
