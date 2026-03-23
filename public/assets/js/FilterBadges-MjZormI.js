// assets/js/FilterBadges.js

/**
 * Module pour gérer les badges de filtres sélectionnés
 * Chaque badge représente un filtre actif et peut être supprimé individuellement
 */
export default class FilterBadges {
  /**
   * @param {HTMLElement} container - conteneur des badges (data-target="filters-summary")
   * @param {HTMLFormElement} form - formulaire des filtres
   * @param {Function} callback - fonction à appeler après modification (ex : submit AJAX)
   */
  constructor(container, form, callback) {
    if (!container) {
      console.warn("FilterBadges : container introuvable");
      return;
    }
    if (!(form instanceof HTMLFormElement)) {
      console.warn("FilterBadges : formulaire invalide");
      return;
    }

    this.container = container;
    this.form = form;
    this.callback = callback;

    // Objet qui contient l'état actuel des filtres pour afficher les badges
    this.filters = {};

    // Initialisation
    this.renderBadges();
    this.initEvents();
  }

  /**
   * Initialisation des événements sur les badges
   */
  initEvents() {
    // Délégation : clic sur un badge pour supprimer le filtre correspondant
    this.container.addEventListener("click", e => {
      const badge = e.target.closest(".filter-badge");
      if (!badge) return;

      const name = badge.dataset.name; // ex: "brand"
      const value = badge.dataset.value; // ex: "2"

      // Supprimer la valeur du formulaire
      const input = this.form.querySelector(
        `input[name="filters[${name}][]"][value="${value}"]`
      );
      if (input) input.checked = false;

      // Mettre à jour les sliders si nécessaire
      const sliderMin = this.form.querySelector(
        `input[name="filters[${name}Min]"]`
      );
      const sliderMax = this.form.querySelector(
        `input[name="filters[${name}Max]"]`
      );
      if (sliderMin && sliderMax) {
        sliderMin.value = sliderMin.min || 0;
        sliderMax.value = sliderMax.max || 300000;
      }

      // Mettre à jour les badges
      this.renderBadges();

      // Déclencher la callback (submit AJAX)
      if (typeof this.callback === "function") this.callback();
    });
  }

  /**
   * Met à jour la liste des badges à partir du formulaire
   */
  renderBadges() {
    const formData = new FormData(this.form);
    this.filters = {};

    // Extraire les filtres
    for (const [key, value] of formData.entries()) {
      const match = key.match(/^filters\[(.+?)\](\[\])?$/);
      if (!match) continue;

      const name = match[1];
      const isArray = !!match[2];

      if (isArray) {
        if (!this.filters[name]) this.filters[name] = [];
        this.filters[name].push(value);
      } else {
        this.filters[name] = value;
      }
    }

    // Nettoyer le conteneur
    this.container.innerHTML = "";

    // Aucun filtre actif
    const hasFilters = Object.keys(this.filters).length > 0;
    if (!hasFilters) {
      this.container.innerHTML =
        '<p class="text-center text-muted">Aucun filtre appliqué</p>';
      return;
    }

    // Construire les badges
    for (const [name, values] of Object.entries(this.filters)) {
      if (Array.isArray(values)) {
        values.forEach(value => {
          const badge = document.createElement("span");
          badge.className = "filter-badge";
          badge.dataset.name = name;
          badge.dataset.value = value;
          badge.textContent = `${value} ×`; // Ici tu peux adapter pour afficher le nom au lieu de l'id
          this.container.appendChild(badge);
        });
      } else {
        const badge = document.createElement("span");
        badge.className = "filter-badge";
        badge.dataset.name = name;
        badge.dataset.value = values;
        badge.textContent = `${values} ×`; // Ici aussi, adapter pour nom lisible
        this.container.appendChild(badge);
      }
    }
  }
}
