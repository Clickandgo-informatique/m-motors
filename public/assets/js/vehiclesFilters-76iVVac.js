// vehiclesFilters.js

export default class VehiclesFilter {
  constructor(form) {
    if (!form) {
      console.warn("VehiclesFilter : form introuvable");
      return;
    }

    this.form = form;
    this.url = form.dataset.fetchUrl;

    if (!this.url) {
      console.warn("VehiclesFilter : data-fetch-url manquant");
      return;
    }

    console.log("VehiclesFilter OK");

    this.initEvents();
  }

  initEvents() {
    // CHECKBOXES
    this.form.addEventListener("change", e => {
      if (e.target.matches("input")) {
        console.log("Changement détecté");
        this.submitFilters();
      }
    });

    // PAGINATION (delegation globale)
    document.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;

      e.preventDefault();

      const page = parseInt(btn.dataset.page);
      if (isNaN(page)) return;

      console.log("Pagination :", page);
      this.submitFilters(page);
    });
  }

  async submitFilters(page = 1) {
    const formData = new FormData(this.form);
    const filters = {};

    for (const [key, value] of formData.entries()) {
      if (key.endsWith("[]")) {
        const cleanKey = key.replace(/\[\]$/, "");
        filters[cleanKey] = filters[cleanKey] || [];
        filters[cleanKey].push(value);
      } else {
        filters[key] = value;
      }
    }

    console.log("Filters envoyés :", filters);

    const res = await fetch(this.url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({ filters, page })
    });

    const data = await res.json();

    this.update(data);
  }

  update(data) {
    document.querySelector("#vehicles-results").innerHTML = data.results;
    document.querySelector(".pagination-wrapper.top").innerHTML =
      data.paginationTop;
    document.querySelector(".pagination-wrapper.bottom").innerHTML =
      data.paginationBottom;
  }
}

/**
 * INIT AUTOMATIQUE QUAND LE FORM APPARAIT
 */
function watchFiltersForm() {
  const observer = new MutationObserver(() => {
    const form = document.querySelector("#filters-form");

    if (form && !form.dataset.initialized) {
      console.log("Form détecté → init");

      form.dataset.initialized = "true";
      new VehiclesFilter(form);
    }
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true
  });
}

document.addEventListener("DOMContentLoaded", () => {
  watchFiltersForm();
});
