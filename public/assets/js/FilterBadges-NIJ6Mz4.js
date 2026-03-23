// FilterBadges.js

/**
 * Gestion des badges de filtres interactifs
 * Chaque badge représente un filtre actif et peut être supprimé pour désactiver le filtre
 */
export default class FilterBadges {
  /**
   * @param {HTMLElement} container - conteneur des badges (summary)
   * @param {HTMLFormElement} form - formulaire lié
   * @param {Function} submitCallback - callback à appeler après suppression d’un filtre
   */
  constructor(container, form, submitCallback) {
    this.container = container;
    this.form = form;
    this.submitCallback = submitCallback;

    // Bind pour pouvoir enlever l’event listener plus tard
    this.handleClick = this.handleClick.bind(this);

    // Écoute des clicks sur les badges
    this.container.addEventListener("click", this.handleClick);

    console.log("FilterBadges initialisé");
  }

  /**
   * Gestion du click sur un badge
   * @param {Event} e
   */
  handleClick(e) {
    if (!e.target.matches(".badge-remove")) return;

    const filter = e.target.dataset.filter;
    const value = e.target.dataset.value;
    if (!filter || !value) return;

    // Désélection des checkboxes multiples
    const checkboxes = this.form.querySelectorAll(
      `input[name="filters[${filter}][]"]`
    );
    checkboxes.forEach(cb => {
      if (cb.value === value) cb.checked = false;
    });

    // Cas sliders (exemple : mileage)
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

    // Relancer le filtrage AJAX
    if (this.submitCallback) this.submitCallback();
  }

  /**
   * Supprime tous les écouteurs et références internes
   * à appeler avant de recréer une instance
   */
  destroy() {
    if (this.container) {
      this.container.removeEventListener("click", this.handleClick);
    }
    this.container = null;
    this.form = null;
    this.submitCallback = null;

    console.log("FilterBadges détruit");
  }
}
