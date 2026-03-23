// FilterBadges.js
export default class FilterBadges {
  constructor(container, form, submitCallback) {
    this.container = container;
    this.form = form;
    this.submitCallback = submitCallback;
    this.listeners = [];
    this.init();
  }

  init() {
    // Détection clic sur badge
    const clickHandler = e => {
      if (!e.target.matches(".badge-remove")) return;
      const filter = e.target.dataset.filter;
      const value = e.target.dataset.value;

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

      if (this.submitCallback) this.submitCallback();
    };

    this.container.addEventListener("click", clickHandler);
    this.listeners.push({ type: "click", handler: clickHandler });
  }

  render(filters) {
    if (!filters || Object.keys(filters).length === 0) {
      this.container.innerHTML =
        '<p class="text-center text-muted">Aucun filtre appliqué</p>';
      return;
    }

    const badges = [];

    for (const key in filters) {
      const value = filters[key];
      if (Array.isArray(value)) {
        value.forEach(v => badges.push(this.buildBadge(key, v)));
      } else {
        badges.push(this.buildBadge(key, value));
      }
    }

    this.container.innerHTML = badges.join(" ");
  }

  buildBadge(filter, value) {
    return `<span class="badge badge-remove" data-filter="${filter}" data-value="${value}">
      ${filter}: ${value} ×
    </span>`;
  }

  destroy() {
    this.listeners.forEach(l =>
      this.container.removeEventListener(l.type, l.handler)
    );
    this.listeners = [];
  }
}
