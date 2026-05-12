export default class FilterBadges {
  constructor(container, form, onRemoveCallback) {
    this.container = container;
    this.form = form;
    this.onRemoveCallback = onRemoveCallback;

    if (!this.container || !this.form) return;

    this.bindEvents();
  }

  /**
   * Gestion des clics sur les badges
   */
  bindEvents() {
    this.container.addEventListener("click", e => {
      const btn = e.target.closest(".badge-remove");
      if (!btn) return;

      const badge = btn.closest(".badge-filter");
      if (!badge) return;

      const filter = badge.dataset.filter;
      const value = badge.dataset.value;

      // Suppression checkbox multiple
      const checkboxes = this.form.querySelectorAll(
        `input[name="filters[${filter}][]"]`
      );

      if (checkboxes.length) {
        checkboxes.forEach(cb => {
          if (cb.value === value) cb.checked = false;
        });
      }

      // Reset sliders
      if (filter === "mileage" || filter === "year") {
        this.resetSlider(filter);
      }

      badge.remove();

      if (typeof this.onRemoveCallback === "function") {
        this.onRemoveCallback();
      }
    });
  }

  /**
   * Reset propre des sliders
   */
  resetSlider(filter) {
    const sliderEl = this.form.querySelector(
      `.double-slider[data-filter="${filter}"]`
    );

    if (!sliderEl) return;

    const defaultMin = parseInt(sliderEl.dataset.min, 10);
    const defaultMax = parseInt(sliderEl.dataset.max, 10);

    const inputMin = this.form.querySelector(
      `input[name="filters[${filter}Min]"]`
    );

    const inputMax = this.form.querySelector(
      `input[name="filters[${filter}Max]"]`
    );

    if (inputMin) inputMin.value = defaultMin;
    if (inputMax) inputMax.value = defaultMax;

    sliderEl.dispatchEvent(
      new CustomEvent("sliderChanged", {
        bubbles: true,
        detail: {
          filter,
          min: defaultMin,
          max: defaultMax
        }
      })
    );
  }

  /**
   * Reconstruction complète des badges depuis le form
   */
  updateBadges() {
    if (!this.container || !this.form) return;

    this.container.innerHTML = "";

    const formData = new FormData(this.form);

    for (const [key, value] of formData.entries()) {
      const match = key.match(/^filters\[(.+?)\](\[\])?$/);
      if (!match) continue;

      const name = match[1];
      const isArray = !!match[2];

      // Checkbox multiples
      if (isArray && value) {
        const input = this.form.querySelector(
          `input[name="filters[${name}][]"][value="${value}"]`
        );

        const label = input
          ? this.form.querySelector(`label[for="${input.id}"]`)?.textContent
          : value;

        this.createBadge(name, value, label);
      }

      // Sliders
      else if (
        ["mileageMin", "mileageMax", "yearMin", "yearMax"].includes(name)
      ) {
        if (name.endsWith("Min")) {
          const filter = name.replace("Min", "");

          const min = value;
          const max = formData.get(`filters[${filter}Max]`);

          this.createBadge(filter, `${min}-${max}`);
        }
      }
    }
  }

  /**
   * Création d'un badge
   */
  createBadge(filter, value, label = null) {
    const badge = document.createElement("span");
    badge.className = "badge badge-filter";
    badge.dataset.filter = filter;
    badge.dataset.value = value;

    if (!label) {
      if (filter === "year") {
        const [min, max] = value.split("-");
        label = `Années : ${min} → ${max}`;
      } else if (filter === "mileage") {
        const [min, max] = value.split("-");
        label =
          `Kilométrage : ${Number(min).toLocaleString("fr-FR")} → ` +
          `${Number(max).toLocaleString("fr-FR")} km`;
      } else {
        label = value;
      }
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
