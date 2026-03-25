/**
 * FilterBadges.js
 * Gestion des badges côté client et suppression
 */
export default class FilterBadges {
  constructor(container, form, onRemoveCallback) {
    this.container = container;
    this.form = form;
    this.onRemoveCallback = onRemoveCallback;

    // Écoute des clics sur les badges pour suppression
    this.container.addEventListener("click", e => {
      if (!e.target.classList.contains("badge-remove")) return;

      const badge = e.target.closest(".badge-filter");
      if (!badge) return;

      const filter = badge.dataset.filter;
      const value = badge.dataset.value;

      // Checkbox multiples
      const checkboxes = this.form.querySelectorAll(
        `input[name="filters[${filter}][]"]`
      );
      checkboxes.forEach(cb => {
        if (cb.value === value) cb.checked = false;
      });

      // Sliders (mileage ou year)
      if (filter === "mileage" || filter === "year") {
        const [min, max] = value.split("-");
        const inputMin = this.form.querySelector(
          `input[name="filters[${filter}Min]"]`
        );
        const inputMax = this.form.querySelector(
          `input[name="filters[${filter}Max]"]`
        );
        if (inputMin && inputMax) {
          // Remise aux bornes par défaut (min/max HTML)
          const sliderEl = this.form.querySelector(
            `.double-slider[data-filter="${filter}"]`
          );
          const sliderMin = parseInt(sliderEl.dataset.min, 10);
          const sliderMax = parseInt(sliderEl.dataset.max, 10);
          inputMin.value = sliderMin;
          inputMax.value = sliderMax;

          // Déclenche événement custom pour remettre à jour le slider visuellement
          sliderEl.dispatchEvent(
            new CustomEvent("sliderChanged", {
              bubbles: true,
              detail: { filter, min: sliderMin, max: sliderMax }
            })
          );
        }
      }

      // Suppression du badge DOM
      badge.remove();

      // Callback pour relancer le filtrage AJAX
      if (typeof this.onRemoveCallback === "function") {
        this.onRemoveCallback();
      }
    });
  }

  /**
   * Met à jour tous les badges existants à partir des valeurs du formulaire
   */
  updateBadges() {
    if (!this.container) return;
    this.container.innerHTML = "";

    const formData = new FormData(this.form);

    for (const [key, value] of formData.entries()) {
      const match = key.match(/^filters\[(.+?)\](\[\])?$/);
      if (!match) continue;
      const name = match[1];
      const isArray = !!match[2];

      // Checkbox multiples
      if (isArray && value) {
        this.createBadge(name, value);
      }
      // Sliders : mileage / year
      else if (
        value &&
        ["mileageMin", "mileageMax", "yearMin", "yearMax"].includes(name)
      ) {
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

  /**
   * Crée un badge et l’ajoute au container
   */
  createBadge(filter, value) {
    const badge = document.createElement("span");
    badge.className = "badge badge-filter";
    badge.dataset.filter = filter;
    badge.dataset.value = value;

    // Label lisible
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

    const removeBtn = document.createElement("button");
    removeBtn.type = "button";
    removeBtn.className = "badge-remove";
    removeBtn.textContent = "×";
    badge.appendChild(removeBtn);

    this.container.appendChild(badge);
  }
}
