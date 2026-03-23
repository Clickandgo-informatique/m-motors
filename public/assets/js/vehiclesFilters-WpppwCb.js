// vehiclesFilters-corrige.js
export default class VehiclesFilter {
  constructor(container = document) {
    console.log(
      "[VehiclesFilter] Initialisation dans le container :",
      container
    );

    // Formulaire
    this.form = container.querySelector("[data-fetch-form]");
    if (!this.form) {
      console.error("[VehiclesFilter] Formulaire non trouvé !");
      return;
    }

    this.fetchUrl = this.form.dataset.fetchUrl;
    if (!this.fetchUrl) {
      console.error("[VehiclesFilter] data-fetch-url manquant !");
      return;
    }

    // Targets pour injection AJAX
    this.resultsTarget = container.querySelector(
      "[data-target='vehicles-search-results']"
    );
    this.paginationTopTarget = container.querySelector(
      "[data-target='pagination-top']"
    );
    this.paginationBottomTarget = container.querySelector(
      "[data-target='pagination-bottom']"
    );

    console.log("[VehiclesFilter] Form et targets détectés :", {
      form: this.form,
      resultsTarget: this.resultsTarget,
      paginationTopTarget: this.paginationTopTarget,
      paginationBottomTarget: this.paginationBottomTarget
    });

    this.bindEvents();
  }

  bindEvents() {
    // Détecte tout changement sur le formulaire
    this.form.addEventListener("change", () => {
      console.log("[VehiclesFilter] change détecté");
      this.submitFilters(1);
    });

    // Pagination click
    document.addEventListener("click", e => {
      const link = e.target.closest("[data-page]");
      if (link) {
        e.preventDefault();
        const page = parseInt(link.dataset.page);
        if (!isNaN(page)) {
          console.log("[VehiclesFilter] pagination clic page :", page);
          this.submitFilters(page);
        }
      }
    });
  }

  async submitFilters(page = 1) {
    const formData = new FormData(this.form);
    const filters = {};

    formData.forEach((val, key) => {
      const cleanKey = key.replace(/\[\]$/, "");
      if (filters[cleanKey])
        filters[cleanKey] = [].concat(filters[cleanKey], val);
      else filters[cleanKey] = [val];
    });

    console.log("[VehiclesFilter] Envoi AJAX page", page, "filters", filters);

    try {
      const res = await fetch(`${this.fetchUrl}?page=${page}`, {
        method: "POST",
        body: JSON.stringify({ filters }),
        headers: {
          "Content-Type": "application/json"
        }
      });

      const data = await res.json();

      if (this.resultsTarget) this.resultsTarget.innerHTML = data.results;
      if (this.paginationTopTarget)
        this.paginationTopTarget.innerHTML = data.paginationTop;
      if (this.paginationBottomTarget)
        this.paginationBottomTarget.innerHTML = data.paginationBottom;

      console.log("[VehiclesFilter] Résultats mis à jour");
    } catch (e) {
      console.error("[VehiclesFilter] Erreur AJAX :", e);
    }
  }
}

// Initialisation automatique
document.addEventListener("DOMContentLoaded", () => {
  console.log("[VehiclesFilter] DOMContentLoaded");
  new VehiclesFilter(document); // container = document pour inclure tout le DOM
});
