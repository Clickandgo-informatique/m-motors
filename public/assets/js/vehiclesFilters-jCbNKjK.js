// assets/js/VehiclesFilter.js
export default class VehiclesFilter {
  /**
   * Initialise le module VehiclesFilter
   * @param {string} formSelector Sélecteur CSS du formulaire (par défaut : [data-fetch-form])
   */
  constructor(formSelector = "[data-fetch-form]") {
    // Récupère le formulaire
    this.form = document.querySelector(formSelector);
    if (!this.form) return;

    // URL pour la requête AJAX
    this.fetchUrl = this.form.dataset.fetchUrl;
    if (!this.fetchUrl) {
      console.warn(
        "VehiclesFilter: data-fetch-url manquant sur le formulaire."
      );
      return;
    }

    // Conteneurs pour injection AJAX
    this.resultsTarget = document.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = document.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = document.querySelector(
      "[data-target='pagination-bottom']"
    );

    if (!this.resultsTarget)
      console.warn(
        "VehiclesFilter: data-target='vehicles-search-results' non trouvé"
      );
    if (!this.paginationTopTarget)
      console.warn("VehiclesFilter: data-target='pagination-top' non trouvé");
    if (!this.paginationBottomTarget)
      console.warn(
        "VehiclesFilter: data-target='pagination-bottom' non trouvé"
      );

    // Initialisation des doublesliders
    this.sliders = [];
    this.initSliders();

    // Lie les événements
    this.bindEvents();
  }

  /**
   * Initialise tous les doublesliders du formulaire
   */
  initSliders() {
    this.form.querySelectorAll(".double-slider").forEach(slider => {
      const min = parseInt(slider.dataset.min) || 0;
      const max = parseInt(slider.dataset.max) || 100;
      const step = parseInt(slider.dataset.step) || 1;

      // Valeurs par défaut
      slider.valueLow = min;
      slider.valueHigh = max;

      // Stocke le slider pour référence
      this.sliders.push(slider);

      // Événement input pour mise à jour visuelle
      slider.addEventListener("input", () => this.updateSliderDisplay(slider));
      // Initialisation affichage
      this.updateSliderDisplay(slider);
    });
  }

  /**
   * Met à jour l'affichage d'un slider (ex: <span> avec min/max)
   * @param {HTMLElement} slider
   */
  updateSliderDisplay(slider) {
    const targetMin = document.getElementById(
      `${slider.dataset.filter}-min-value`
    );
    const targetMax = document.getElementById(
      `${slider.dataset.filter}-max-value`
    );

    if (targetMin) targetMin.textContent = slider.valueLow.toLocaleString();
    if (targetMax) targetMax.textContent = slider.valueHigh.toLocaleString();
  }

  /**
   * Lie les événements du formulaire et de la pagination
   */
  bindEvents() {
    // Soumission du formulaire
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.submitFilters();
    });

    // Changement sur inputs et selects
    this.form.querySelectorAll("input, select").forEach(el => {
      el.addEventListener("change", () => this.submitFilters());
    });

    // Clic sur les liens de pagination
    document.addEventListener("click", e => {
      const link = e.target.closest(".pagination-link");
      if (link) {
        e.preventDefault();
        const page = link.dataset.page || 1;
        this.submitFilters(page);
      }
    });
  }

  /**
   * Envoie les filtres via AJAX et met à jour la page
   * @param {number} page Numéro de page pour la pagination
   */
  async submitFilters(page = 1) {
    if (!this.fetchUrl) return;

    // Récupère les données du formulaire
    const formData = new FormData(this.form);

    // Transforme FormData en objet JSON, gère les tableaux pour les checkboxes multiples
    const filters = {};
    formData.forEach((val, key) => {
      if (filters[key]) filters[key] = [].concat(filters[key], val);
      else filters[key] = val;
    });

    // Ajout des valeurs des doublesliders
    this.sliders.forEach(slider => {
      filters[`${slider.dataset.filter}Min`] = slider.valueLow;
      filters[`${slider.dataset.filter}Max`] = slider.valueHigh;
    });

    try {
      // Requête AJAX POST
      const res = await fetch(`${this.fetchUrl}?page=${page}`, {
        method: "POST",
        body: JSON.stringify({ filters, q: filters.q || null }),
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const data = await res.json();

      // Injection du HTML dans les conteneurs
      if (this.resultsTarget && data.results !== undefined)
        this.resultsTarget.innerHTML = data.results;
      if (this.paginationTopTarget && data.paginationTop !== undefined)
        this.paginationTopTarget.innerHTML = data.paginationTop;
      if (this.paginationBottomTarget && data.paginationBottom !== undefined)
        this.paginationBottomTarget.innerHTML = data.paginationBottom;
    } catch (e) {
      console.error("VehiclesFilter AJAX error:", e);
    }
  }
}
