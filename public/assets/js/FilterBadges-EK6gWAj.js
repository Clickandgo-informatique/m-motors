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

    // Initialisation
    this.init();
    // Création initiale des badges
    this.updateBadges();
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

      // Mise à jour des badges
      this.updateBadges();

      // Déclenche filtrage AJAX
      if (typeof this.submitCallback === "function") {
        this.submitCallback();
      }
    };

    this.container.addEventListener("click", clickHandler);
    this.listeners.push({ type: "click", handler: clickHandler });

    console.log("FilterBadges → event listener ajouté sur container");
  }

  /**
   * Crée / met à jour les badges selon les filtres actifs
   */
  updateBadges() {
    if (!this.container) return;
    this.container.innerHTML = ""; // vide le container

    const badges = [];

    // Checkboxes
    const checkboxInputs = this.form.querySelectorAll('input[type="checkbox"]');
    checkboxInputs.forEach(cb => {
      if (cb.checked) {
        const badge = document.createElement("span");
        badge.className = "badge";
        badge.textContent = cb.closest("li").querySelector("label").textContent;

        const remove = document.createElement("span");
        remove.className = "badge-remove";
        const match = cb.name.match(/^filters\[(.+?)\]\[\]$/);
        remove.dataset.filter = match ? match[1] : "";
        remove.dataset.value = cb.value;
        remove.textContent = " ×";

        badge.appendChild(remove);
        badges.push(badge);
      }
    });

    // Slider (ex: mileage)
    const minInput = this.form.querySelector(
      'input[name="filters[mileageMin]"]'
    );
    const maxInput = this.form.querySelector(
      'input[name="filters[mileageMax]"]'
    );
    if (
      minInput &&
      maxInput &&
      (minInput.value != "0" || maxInput.value != "300000")
    ) {
      const badge = document.createElement("span");
      badge.className = "badge";
      badge.textContent = `Kilométrage: ${minInput.value} - ${maxInput.value}`;

      const remove = document.createElement("span");
      remove.className = "badge-remove";
      remove.dataset.filter = "mileage";
      remove.dataset.value = `${minInput.value}-${maxInput.value}`;
      remove.textContent = " ×";
      badge.appendChild(remove);

      badges.push(badge);
    }

    if (badges.length > 0) {
      badges.forEach(b => this.container.appendChild(b));
    } else {
      this.container.innerHTML =
        '<p class="text-center text-muted">Aucun filtre appliqué</p>';
    }
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
