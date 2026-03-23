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

    // Stockage des event listeners pour destroy
    this.listeners = [];

    console.log("FilterBadges initialisé :", container);

    // Initialisation
    this.init();
  }

  /**
   * Initialisation : écoute les clics sur les badges
   */
  init() {
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
  }

  /**
   * Met à jour les badges en reconstruisant leur contenu
   * Doit être appelé après chaque filtrage AJAX
   */
  update() {
    if (!this.container) return;

    const badgesHtml = [];

    const formData = new FormData(this.form);

    for (const [key, value] of formData.entries()) {
      const match = key.match(/^filters\[(.+?)\](\[\])?$/);
      if (!match) continue;

      const name = match[1];
      const isArray = !!match[2];

      if (isArray) {
        // plusieurs valeurs
        const values = formData.getAll(key);
        values.forEach(v => {
          badgesHtml.push(
            `<span class="badge" data-filter="${name}" data-value="${v}">
              ${v} <span class="badge-remove" data-filter="${name}" data-value="${v}">&times;</span>
            </span>`
          );
        });
      } else {
        // valeur unique
        if (value !== "") {
          badgesHtml.push(
            `<span class="badge" data-filter="${name}" data-value="${value}">
              ${value} <span class="badge-remove" data-filter="${name}" data-value="${value}">&times;</span>
            </span>`
          );
        }
      }
    }

    if (badgesHtml.length > 0) {
      this.container.innerHTML = badgesHtml.join(" ");
    } else {
      this.container.innerHTML =
        '<p class="text-center text-muted">Aucun filtre appliqué</p>';
    }
  }

  /**
   * Détruit l'instance et supprime tous les event listeners
   */
  destroy() {
    this.listeners.forEach(l => {
      this.container.removeEventListener(l.type, l.handler);
    });
    this.listeners = [];
    console.log("FilterBadges détruit");
  }
}
