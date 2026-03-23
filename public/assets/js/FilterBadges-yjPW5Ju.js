// FilterBadges.js

/**
 * Gère l'affichage interactif des badges de filtres
 * Chaque badge peut supprimer le filtre correspondant
 */
export default class FilterBadges {
  /**
   * @param {HTMLElement} container → élément qui contiendra les badges
   * @param {HTMLFormElement} form → formulaire lié aux filtres
   * @param {Function} submitCallback → fonction à appeler après suppression d'un filtre
   */
  constructor(container, form, submitCallback) {
    this.container = container;
    this.form = form;
    this.submitCallback = submitCallback;
    this.listeners = [];

    console.log("FilterBadges initialisé :", container);

    this.init();
  }

  init() {
    // Délégué sur le container (évite duplication de listeners)
    this._clickHandler = e => {
      if (!e.target.matches(".badge-remove")) return;

      const filter = e.target.dataset.filter;
      const value = e.target.dataset.value;
      if (!filter || !value) return;

      // Checkboxes multiples
      const checkboxes = this.form.querySelectorAll(
        `input[name="filters[${filter}][]"]`
      );
      checkboxes.forEach(cb => {
        if (cb.value === value) cb.checked = false;
      });

      // Sliders (ex: mileage)
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

      if (typeof this.submitCallback === "function") {
        this.submitCallback();
      }
    };

    this.container.addEventListener("click", this._clickHandler);
    this.listeners.push({ type: "click", handler: this._clickHandler });
  }

  destroy() {
    this.listeners.forEach(l => {
      this.container.removeEventListener(l.type, l.handler);
    });
    this.listeners = [];
    console.log("FilterBadges détruit");
  }
}
