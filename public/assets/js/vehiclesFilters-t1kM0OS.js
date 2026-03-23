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

    // Tableau pour stocker les event listeners afin de pouvoir les retirer
    this.listeners = [];

    console.log("FilterBadges initialisé :", container);

    // Initialisation
    this.init();
  }

  /**
   * Initialisation : écoute les clics sur les badges
   */
  init() {
    // Délégué sur le container
    const clickHandler = e => {
      if (!e.target.matches(".badge-remove")) return;

      const filter = e.target.dataset.filter;
      const value = e.target.dataset.value;
      if (!filter || !value) return;

      // Cas checkbox multiples
      const checkboxes = this.form.querySelectorAll(
        `input[name="filters[${filter}][]"]`
      );
      checkboxes.forEach(cb => {
        if (cb.value === value) cb.checked = false;
      });

      // Cas sliders (ex: mileage)
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

      // Relance filtrage AJAX
      if (typeof this.submitCallback === "function") {
        this.submitCallback();
      }
    };

    this.container.addEventListener("click", clickHandler);
    this.listeners.push({ type: "click", handler: clickHandler });

    console.log("FilterBadges → event listener ajouté sur container");
  }

  /**
   * Détruit l'instance en supprimant tous les event listeners
   */
  destroy() {
    this.listeners.forEach(l => {
      this.container.removeEventListener(l.type, l.handler);
    });
    this.listeners = [];
    console.log("FilterBadges détruit");
  }
}
