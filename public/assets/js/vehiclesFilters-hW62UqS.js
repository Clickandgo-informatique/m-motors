// vehiclesFilters.js

/**
 * Module de gestion des filtres véhicules + pagination AJAX + badges
 * Compatible avec formulaire chargé dynamiquement (fragment sidebar)
 */
export default class VehiclesFilter {
  /**
   * @param {HTMLFormElement} form
   */
  constructor(form) {
    console.log("INIT VehiclesFilter → élément reçu :", form);

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

    // Initialisation slider si présent
    this.initSlider();

    // Initialisation des events (checkboxes, pagination, badges)
    this.initEvents();
  }

  /**
   * Initialisation du slider (double-slider)
   */
  initSlider() {
    const slider = this.form.querySelector(".double-slider");
    if (!slider || typeof initDoubleSlider !== "function") return;

    initDoubleSlider(slider);

    document.addEventListener("sliderChanged", e => {
      const { filter, min, max } = e.detail;
      const inputMin = this.form.querySelector(
        `input[name="filters[${filter}Min]"]`
      );
      const inputMax = this.form.querySelector(
        `input[name="filters[${filter}Max]"]`
      );
      if (inputMin && inputMax) {
        inputMin.value = min;
        inputMax.value = max;
      }
      this.submitFilters();
    });
  }

  /**
   * Initialise tous les événements
   */
  initEvents() {
    // 1. Changement dans le formulaire (checkbox, slider, select...)
    this.form.addEventListener("change", e => {
      if (!e.target.matches("input")) return;
      this.submitFilters();
    });

    // 2. Pagination
    document.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;
      e.preventDefault();
      const page = Number.parseInt(btn.dataset.page);
      if (Number.isNaN(page)) return;
      this.submitFilters(page);
    });

    // 3. Clic sur badge pour supprimer un filtre
    this.form.addEventListener("click", e => {
      const badge = e.target.closest(".filter-badge");
      if (!badge) return;

      const filterName = badge.dataset.filter;
      const value = badge.dataset.value;

      // Désélectionner la checkbox correspondante
      const checkbox = this.form.querySelector(
        `input[name="filters[${filterName}][]"][value="${value}"]`
      );
      if (checkbox) checkbox.checked = false;

      // Relance AJAX
      this.submitFilters();
    });
  }

  /**
   * Construit les filtres et envoie la requête AJAX
   * @param {number} page
   */
  async submitFilters(page = 1) {
    const formData = new FormData(this.form);
    const filters = {};

    for (const [key, value] of formData.entries()) {
      const match = key.match(/^filters\[(.+?)\](\[\])?$/);
      if (!match) continue;

      const name = match[1];
      const isArray = !!match[2];

      if (isArray) {
        if (!filters[name]) filters[name] = [];
        filters[name].push(value);
      } else {
        filters[name] = value;
      }
    }

    console.log("Filters envoyés :", filters);

    try {
      const response = await fetch(this.url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters, page })
      });

      const data = await response.json();
      this.updateDOM(data, filters);
    } catch (err) {
      console.error("Erreur AJAX :", err);
    }
  }

  /**
   * Met à jour le DOM avec les fragments retournés
   */
  updateDOM(data, filters) {
    // 1. Résultats véhicules
    const results = document.querySelector(
      '[data-target="vehicles-search-results"]'
    );
    if (results && data.results) results.innerHTML = data.results;

    // 2. Pagination Top
    const top = document.querySelector('[data-target="pagination-top"]');
    if (top && data.paginationTop) top.innerHTML = data.paginationTop;

    // 3. Pagination Bottom
    const bottom = document.querySelector('[data-target="pagination-bottom"]');
    if (bottom && data.paginationBottom)
      bottom.innerHTML = data.paginationBottom;

    // 4. Badges / résumé des filtres
    const summary = document.querySelector('[data-target="filters-summary"]');
    if (summary) {
      summary.innerHTML = ""; // on vide le conteneur

      const badges = [];

      // Parcours des filtres pour créer les badges
      for (const key in filters) {
        const value = filters[key];

        // Les filtres multiples (checkboxes)
        if (Array.isArray(value)) {
          value.forEach(v => {
            const badge = document.createElement("span");
            badge.className = "filter-badge";
            badge.dataset.filter = key;
            badge.dataset.value = v;
            badge.textContent = this.getLabel(key, v) + " ×";
            badges.push(badge);
          });
        } else {
          // filtre simple (slider min/max)
          if (key.endsWith("Min") || key.endsWith("Max")) continue; // on gère dans slider
        }
      }

      if (badges.length === 0) {
        summary.innerHTML =
          '<p class="text-center text-muted">Aucun filtre appliqué</p>';
      } else {
        badges.forEach(b => summary.appendChild(b));
      }
    }
  }

  /**
   * Retourne le texte lisible pour un filtre
   * @param {string} key
   * @param {string} value
   */
  getLabel(key, value) {
    // Ici tu peux utiliser tes arrays "brands", "bodyTypes", "fuelTypes"
    // Exemple : si tu passes ces arrays au constructor ou via dataset
    if (!window.FILTER_LABELS) return value; // fallback
    const mapping = window.FILTER_LABELS[key] || [];
    const found = mapping.find(item => String(item.id) === String(value));
    return found ? found.name : value;
  }
}

/**
 * Observer → détecte l’apparition du formulaire dans le DOM
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
