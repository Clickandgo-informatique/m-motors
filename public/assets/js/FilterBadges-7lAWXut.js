// vehicles/js/FilterBadges.js

/**
 * Gestion des badges de filtres interactifs
 * - Génère les badges à partir des inputs du formulaire (checkboxes, sliders)
 * - Permet de supprimer un filtre en cliquant sur le badge
 */
export default class FilterBadges {
  /**
   * @param {HTMLElement} container - conteneur où afficher les badges
   * @param {HTMLFormElement} form - formulaire des filtres
   * @param {Function} onChange - callback à appeler après suppression d’un filtre
   */
  constructor(container, form, onChange) {
    this.container = container;
    this.form = form;
    this.onChange = onChange;

    // Initialisation
    this.renderBadges();

    // Écoute des changements sur le formulaire pour mettre à jour les badges
    this.form.addEventListener("change", () => this.renderBadges());
  }

  /**
   * Génère les badges depuis les valeurs actuelles du formulaire
   */
  renderBadges() {
    const badges = [];

    // Parcours des inputs du formulaire
    const inputs = this.form.querySelectorAll("input");

    inputs.forEach(input => {
      const name = input.name.match(/^filters\[(.+?)\](\[\])?$/)?.[1];
      if (!name) return;

      // Checkbox cochée → badge
      if (input.type === "checkbox" && input.checked) {
        badges.push({
          label: input.dataset.label || input.value,
          filter: name,
          value: input.value
        });
      }

      // Slider → badge
      if (input.type === "hidden" && name.endsWith("Min")) {
        const baseName = name.replace("Min", "");
        const inputMax = this.form.querySelector(
          `input[name="filters[${baseName}Max]"]`
        );
        if (!inputMax) return;

        const min = input.value;
        const max = inputMax.value;

        // Si slider non vide ou non par défaut
        if (min || max) {
          badges.push({
            label: `${baseName.charAt(0).toUpperCase() +
              baseName.slice(1)} : ${min}-${max}`,
            filter: baseName,
            value: `${min}-${max}`
          });
        }
      }
    });

    // Construction du HTML des badges
    if (badges.length === 0) {
      this.container.innerHTML =
        '<p class="text-center text-muted">Aucun filtre appliqué</p>';
      return;
    }

    const html = badges
      .map(
        b => `
        <span class="badge badge-primary">
          ${b.label}
          <span class="badge-remove" data-filter="${b.filter}" data-value="${b.value}">&times;</span>
        </span>`
      )
      .join(" ");

    this.container.innerHTML = html;
  }
}
