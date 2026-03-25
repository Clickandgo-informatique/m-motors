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
      if (checkboxes.length) {
        checkboxes.forEach(cb => {
          if (cb.value === value) cb.checked = false;
        });
      }

      // Sliders (range)
      if (filter === "mileage" || filter === "year") {
        const sliderEl = this.form.querySelector(
          `.double-slider[data-filter="${filter}"]`
        );
        if (sliderEl) {
          const defaultMin = parseInt(sliderEl.dataset.min, 10);
          const defaultMax = parseInt(sliderEl.dataset.max, 10);

          const inputMin = this.form.querySelector(
            `input[name="filters[${filter}Min]"]`
          );
          const inputMax = this.form.querySelector(
            `input[name="filters[${filter}Max]"]`
          );
          if (inputMin && inputMax) {
            inputMin.value = defaultMin;
            inputMax.value = defaultMax;
          }

          sliderEl.dispatchEvent(
            new CustomEvent("sliderChanged", {
              bubbles: true,
              detail: { filter, min: defaultMin, max: defaultMax }
            })
          );
        }
      }

      badge.remove();

      if (typeof this.onRemoveCallback === "function") this.onRemoveCallback();
    });
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

      // Checkbox multiples
      if (isArray && value) {
        const labelEl = this.form.querySelector(
          `input[name="filters[${name}][]"][value="${value}"]`
        );
        const labelText = labelEl
          ? this.form.querySelector(`label[for="${labelEl.id}"]`).textContent
          : value;
        this.createBadge(name, value, labelText);
      }

      // Sliders (mileage / year)
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

  createBadge(filter, value, label = null) {
    const badge = document.createElement("span");
    badge.className = "badge badge-filter";
    badge.dataset.filter = filter;
    badge.dataset.value = value;

    // Label lisible
    if (!label) {
      if (filter === "year") {
        const [min, max] = value.split("-");
        label = `Années : ${min} → ${max}`;
      } else if (filter === "mileage") {
        const [min, max] = value.split("-");
        label = `Kilométrage : ${Number(min).toLocaleString()} → ${Number(
          max
        ).toLocaleString()} km`;
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
