// FilterBadges.js

/**
 * Gère l'affichage interactif des badges de filtres
 * Chaque badge peut supprimer le filtre correspondant
 */
export default class FilterBadges {
  /**
   * @param {HTMLElement} container - Élément qui contiendra les badges
   * @param {HTMLFormElement} form - Formulaire lié aux filtres
   * @param {Function} submitCallback - Fonction à appeler après suppression d'un filtre
   */
  constructor(container, form, submitCallback) {
    this.container = container;
    this.form = form;
    this.submitCallback = submitCallback;

    // Tableau pour stocker les event listeners afin de pouvoir les retirer proprement
    this.listeners = [];

    console.log("FilterBadges initialisé :", container);

    // Initialisation
    this.init();
  }

  /**
   * Initialisation : écoute les clics sur les badges
   */
  init() {
    // Événement délégué sur le container
    const clickHandler = e => {
      const badge = e.target.closest(".badge-remove");
      if (!badge) return;

      const filter = badge.dataset.filter;
      const value = badge.dataset.value;
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
   * Utile avant de remplacer le DOM pour éviter les fuites et doublons
   */
  destroy() {
    this.listeners.forEach(l => {
      this.container.removeEventListener(l.type, l.handler);
    });
    this.listeners = [];
    console.log("FilterBadges détruit");
  }
}
