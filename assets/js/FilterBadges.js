// FilterBadges.js

/**
 * Gère l'affichage interactif des badges de filtres côté client
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
    this.updateBadges();
  }

  init() {
    // Clic sur un badge pour supprimer un filtre
    const clickHandler = e => {
      if (!e.target.matches(".badge-remove")) return;

      const badge = e.target.closest(".badge");
      if (!badge) return;

      const filter = badge.dataset.filter;
      const value = badge.dataset.value;
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

    // Écoute tout changement du formulaire pour mettre à jour les badges
    const changeHandler = e => {
      if (!e.target.matches("input")) return;
      this.updateBadges();
    };
    this.form.addEventListener("change", changeHandler);
    this.listeners.push({ type: "change", handler: changeHandler });

    console.log("FilterBadges → event listeners ajoutés");
  }

  /**
   * Génère et injecte les badges dans le container
   */
  updateBadges() {
    const badges = [];

    // Checkboxes cochées
    const inputs = this.form.querySelectorAll('input[type="checkbox"]:checked');
    inputs.forEach(input => {
      const label = this.form.querySelector(`label[for="${input.id}"]`);
      if (!label) return;
      const nameMatch = input.name.match(/filters\[(.+?)\]/);
      if (!nameMatch) return;
      const filterName = nameMatch[1];

      badges.push(`
        <span class="badge" data-filter="${filterName}" data-value="${input.value}">
          ${label.textContent} <span class="badge-remove">&times;</span>
        </span>
      `);
    });

    // Slider mileage
    const inputMin = this.form.querySelector(
      'input[name="filters[mileageMin]"]'
    );
    const inputMax = this.form.querySelector(
      'input[name="filters[mileageMax]"]'
    );
    if (inputMin && inputMax) {
      const min = inputMin.value;
      const max = inputMax.value;
      if (min !== "0" || max !== "300000") {
        badges.push(`
          <span class="badge" data-filter="mileage" data-value="${min}-${max}">
            Kilométrage: ${min} - ${max} km <span class="badge-remove">&times;</span>
          </span>
        `);
      }
    }

    // Injection dans le container
    if (badges.length > 0) {
      this.container.innerHTML = badges.join("");
    } else {
      this.container.innerHTML =
        '<p class="text-center text-muted">Aucun filtre appliqué</p>';
    }

    console.log("FilterBadges → badges mis à jour :", badges);
  }

  /**
   * Détruit l'instance et supprime tous les listeners
   */
  destroy() {
    this.listeners.forEach(l => {
      this.container.removeEventListener(l.type, l.handler);
      this.form.removeEventListener(l.type, l.handler);
    });
    this.listeners = [];
    console.log("FilterBadges détruit");
  }
}
