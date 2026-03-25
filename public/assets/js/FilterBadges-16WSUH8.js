/**
 * FilterBadges.js
 * Gestion des badges côté client
 */
export default class FilterBadges {
  constructor(container, form, onRemoveCallback) {
    this.container = container;
    this.form = form;
    this.onRemoveCallback = onRemoveCallback;
  }

  updateBadges() {
    if (!this.container) return;
    this.container.innerHTML = "";

    const formData = new FormData(this.form);

    // Parcours des filtres
    for (const [key, value] of formData.entries()) {
      const match = key.match(/^filters\[(.+?)\](\[\])?$/);
      if (!match) continue;
      const name = match[1];
      const isArray = !!match[2];

      // Gestion valeurs multiples (checkboxes)
      if (isArray && value) {
        this.createBadge(name, value);
      }
      // Gestion sliders (mileage, year)
      else if (
        value &&
        ["mileageMin", "mileageMax", "yearMin", "yearMax"].includes(name)
      ) {
        // On ne crée le badge qu’une fois par paire min/max
        if (name.endsWith("Min")) {
          const filter = name.replace("Min", "");
          const min = value;
          const max = formData.get(`filters[${filter}Max]`);
          if (min !== null && max !== null) {
            this.createBadge(filter, `${min}-${max}`);
          }
        }
      }
    }
  }

  createBadge(filter, value) {
    const badge = document.createElement("span");
    badge.className = "badge badge-filter";
    badge.dataset.filter = filter;
    badge.dataset.value = value;

    // Label lisible pour années
    let label = value;
    if (filter === "year") {
      const [min, max] = value.split("-");
      label = `Années : ${min} → ${max}`;
    }
    if (filter === "mileage") {
      const [min, max] = value.split("-");
      label = `Kilométrage : ${Number(min).toLocaleString()} → ${Number(
        max
      ).toLocaleString()} km`;
    }

    badge.textContent = label;

    // Bouton de suppression
    const removeBtn = document.createElement("button");
    removeBtn.type = "button";
    removeBtn.className = "badge-remove";
    removeBtn.textContent = "×";
    badge.appendChild(removeBtn);

    this.container.appendChild(badge);
  }
}
