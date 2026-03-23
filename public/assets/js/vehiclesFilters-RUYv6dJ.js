// vehiclesFilters.js

export default class VehiclesFilter {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) {
      console.error("VehiclesFilter : élément invalide (pas un form)", form);
      return;
    }

    this.form = form;
    this.url = this.form.dataset.fetchUrl;
    if (!this.url) {
      console.error("VehiclesFilter : data-fetch-url manquant");
      return;
    }

    this.initEvents();
  }

  initEvents() {
    this.form.addEventListener("change", e => {
      if (!e.target.matches("input")) return;
      this.submitFilters();
    });

    document.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;
      e.preventDefault();
      const page = parseInt(btn.dataset.page);
      if (isNaN(page)) return;
      this.submitFilters(page);
    });
  }

  async submitFilters(page = 1) {
    const formData = new FormData(this.form);
    const filters = {};

    // ✅ Transformation correcte des clés
    for (const [key, value] of formData.entries()) {
      // Exemple key = "filters[brand][]" ou "filters[mileageMin]"
      const match = key.match(/^filters\[(.+?)\](\[\])?$/);
      if (!match) continue;

      const name = match[1]; // "brand", "mileageMin", "bodyType" etc.
      const isArray = !!match[2];

      if (isArray) {
        if (!filters[name]) filters[name] = [];
        filters[name].push(value);
      } else {
        filters[name] = value;
      }
    }

    console.log("Filters envoyés (corrects) :", filters);

    try {
      const response = await fetch(this.url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters, page })
      });
      const data = await response.json();
      this.updateDOM(data);
    } catch (err) {
      console.error("Erreur AJAX :", err);
    }
  }

  updateDOM(data) {
    const results = document.querySelector("#vehicles-results");
    if (results && data.results) results.innerHTML = data.results;

    const top = document.querySelector(".pagination-wrapper.top");
    if (top && data.paginationTop) top.innerHTML = data.paginationTop;

    const bottom = document.querySelector(".pagination-wrapper.bottom");
    if (bottom && data.paginationBottom)
      bottom.innerHTML = data.paginationBottom;
  }
}

/**
 * Observer pour initialiser le formulaire quand il est chargé via fragment
 */
function watchFiltersForm() {
  const observer = new MutationObserver(() => {
    const form = document.querySelector("#filters-form");
    if (!form || form.dataset.initialized) return;
    form.dataset.initialized = "true";
    new VehiclesFilter(form);
  });

  observer.observe(document.body, { childList: true, subtree: true });
}

document.addEventListener("DOMContentLoaded", watchFiltersForm);
