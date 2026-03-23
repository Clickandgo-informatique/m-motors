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
    const clickHandler = e => {
      if (!e.target.matches(".badge-remove")) return;

      const filter = e.target.dataset.filter;
      const value = e.target.dataset.value;
      if (!filter || !value) return;

      console.log("FilterBadges → suppression filtre :", { filter, value });

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

      // Déclenche filtrage AJAX
      if (typeof this.submitCallback === "function") {
        this.submitCallback();
      }
    };

    this.container.addEventListener("click", clickHandler);
    this.listeners.push({ type: "click", handler: clickHandler });

    console.log("FilterBadges → event listener ajouté sur container");
  }

  destroy() {
    this.listeners.forEach(l => {
      this.container.removeEventListener(l.type, l.handler);
    });
    this.listeners = [];
    console.log("FilterBadges détruit");
  }
}
