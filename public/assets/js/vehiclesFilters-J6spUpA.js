// --- vehiclesFilters.js ---
// Gestion des filtres AJAX, toggle view (table/grid) et pagination
import FilterBadges from "./FilterBadges.js";
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(form) {
    // Vérifie que le paramètre est bien un formulaire
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.url = "/vehicles/filters"; // URL AJAX pour soumettre les filtres

    // Conteneurs principaux pour injecter les résultats et la pagination
    this.resultsContainer = document.querySelector(
      '[data-target="vehicles-search-results"]'
    );
    this.paginationTop = document.querySelector(
      '[data-target="pagination-top"]'
    );
    this.paginationBottom = document.querySelector(
      '[data-target="pagination-bottom"]'
    );
    this.summaryContainer = document.querySelector(
      '[data-target="filters-summary"]'
    );

    // --- Initialisation des badges (résumé des filtres appliqués) ---
    if (this.summaryContainer) {
      this.badges = new FilterBadges(
        this.summaryContainer,
        this.form,
        this.submitFilters.bind(this) // callback pour mettre à jour les résultats
      );
    }

    // --- Initialisation des sliders ---
    this.initSliders();

    // --- Initialisation du toggle view (table / grid) ---
    this.initViewToggle();

    // --- Initialisation des événements (form, pagination, badges, toggle) ---
    this.initEvents();
  }

  // --- Initialisation des sliders doubles ---
  initSliders() {
    const sliders = this.form.querySelectorAll(".double-slider");
    if (!sliders.length || typeof initDoubleSlider !== "function") return;

    sliders.forEach(slider => {
      initDoubleSlider(slider); // Fonction externe qui initialise le slider
      let timer = null;

      // Quand le slider change, on met à jour les inputs correspondants
      slider.addEventListener("sliderChanged", e => {
        const { filter, min, max } = e.detail;
        const inputMin = this.form.querySelector(
          `input[name="filters[${filter}Min]"]`
        );
        const inputMax = this.form.querySelector(
          `input[name="filters[${filter}Max]"]`
        );
        if (inputMin) inputMin.value = min;
        if (inputMax) inputMax.value = max;

        // Débounce pour éviter les multiples appels AJAX
        clearTimeout(timer);
        timer = setTimeout(() => this.submitFilters(), 300);
      });
    });
  }

  // --- Initialisation du toggle view (table / grid) ---
  initViewToggle() {
    this.viewToggle = document.querySelector("#view-switch-form");
    if (!this.viewToggle) return;

    // Récupère la dernière vue sauvegardée côté client (localStorage)
    const savedView = localStorage.getItem("vehicleView");
    if (savedView) {
      const input = this.viewToggle.querySelector(
        `input[name="view"][value="${savedView}"]`
      );
      if (input) input.checked = true;
    }

    // Quand l'utilisateur change le toggle, on sauvegarde et on recharge les résultats
    this.viewToggle.querySelectorAll("input[name='view']").forEach(input => {
      input.addEventListener("change", () => {
        localStorage.setItem("vehicleView", input.value);
        this.submitFilters();
      });
    });
  }

  // --- Initialisation des autres événements ---
  initEvents() {
    // Détection des changements dans le formulaire (checkbox, select, etc.)
    this.form.addEventListener("change", e => {
      if (!e.target.matches("input")) return;
      this.submitFilters();
    });

    // Pagination : clique sur un bouton avec data-page
    document.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;
      e.preventDefault();
      const page = Number.parseInt(btn.dataset.page);
      if (!isNaN(page)) this.submitFilters(page);
    });

    // Suppression des badges
    if (this.summaryContainer) {
      this.summaryContainer.addEventListener("click", e => {
        if (!e.target.matches(".badge-remove")) return;
        const filter = e.target.dataset.filter;
        const value = e.target.dataset.value;

        // Si le filtre est un slider, on reset
        const slider = this.form.querySelector(
          `.double-slider[data-filter="${filter}"]`
        );
        if (slider && typeof slider.resetSlider === "function")
          slider.resetSlider();
        else {
          // Sinon, on décoche la checkbox correspondante
          const checkboxes = this.form.querySelectorAll(
            `input[name="filters[${filter}][]"]`
          );
          checkboxes.forEach(cb => {
            if (cb.value === value) cb.checked = false;
          });
        }

        if (this.badges) this.badges.updateBadges();
        this.submitFilters();
      });
    }
  }

  // --- Soumission AJAX des filtres ---
  async submitFilters(page = 1) {
    const formData = new FormData(this.form);
    const filters = {};

    // On reconstruit un objet JS à partir des inputs name="filters[...]"
    for (const [key, value] of formData.entries()) {
      const match = key.match(/^filters\[(.+?)\](\[\])?$/);
      if (!match) continue;
      const name = match[1];
      const isArray = !!match[2];
      if (isArray) {
        if (!filters[name]) filters[name] = [];
        filters[name].push(value);
      } else filters[name] = value;
    }

    // Ajoute le mode d'affichage (table/grid)
    const viewInput = this.viewToggle.querySelector(
      'input[name="view"]:checked'
    );
    if (viewInput) filters.view = viewInput.value;

    try {
      const res = await fetch(this.url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters, page })
      });
      const data = await res.json();
      this.updateDOM(data);
    } catch (err) {
      console.error("Erreur AJAX:", err);
    }
  }

  // --- Mise à jour du DOM après AJAX ---
  updateDOM(data) {
    if (this.resultsContainer && data.results)
      this.resultsContainer.innerHTML = data.results;
    if (this.paginationTop && data.paginationTop)
      this.paginationTop.innerHTML = data.paginationTop;
    if (this.paginationBottom && data.paginationBottom)
      this.paginationBottom.innerHTML = data.paginationBottom;

    if (this.badges) this.badges.updateBadges();
  }
}

// --- Observer pour attendre que le formulaire soit présent dans le DOM ---
function watchFiltersForm() {
  const observer = new MutationObserver(() => {
    const form = document.querySelector("#filters-form");
    if (!form || form.dataset.initialized) return;
    form.dataset.initialized = "true";
    new VehiclesFilter(form);
  });

  observer.observe(document.body, { childList: true, subtree: true });
}

// --- Initialisation après DOM ready ---
document.addEventListener("DOMContentLoaded", watchFiltersForm);
