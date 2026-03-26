// --- vehiclesFilters.js ---
// Gestion des filtres AJAX et du toggle view
import FilterBadges from "./FilterBadges.js";
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.url = form.dataset.fetchUrl; // URL AJAX côté controller
    if (!this.url) return;

    // Conteneur principal où le controller injecte tout le HTML
    this.resultsContainer = document.querySelector("#vehicles-results");

    // Conteneur des badges
    this.summaryContainer = document.querySelector("#filters-summary");
    if (this.summaryContainer) {
      this.badges = new FilterBadges(
        this.summaryContainer,
        this.form,
        this.submitFilters.bind(this)
      );
    }

    // --- Init sliders ---
    this.initSliders();

    // --- Init toggle view ---
    this.initViewToggle();

    // --- Init événements form / pagination / badges ---
    this.initEvents();
  }

  // --- Initialisation des doubles sliders ---
  initSliders() {
    const sliders = this.form.querySelectorAll(".double-slider");
    if (!sliders.length || typeof initDoubleSlider !== "function") return;

    sliders.forEach(slider => {
      initDoubleSlider(slider);
      let timer = null;

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

        clearTimeout(timer);
        timer = setTimeout(() => this.submitFilters(), 300);
      });
    });
  }

  // --- Toggle radio view (grid/table) ---
  initViewToggle() {
    this.viewToggle = document.querySelector("#view-switch-form");
    if (!this.viewToggle) return;

    // Lecture valeur persistée dans localStorage
    const savedView = localStorage.getItem("vehicleView");
    if (savedView) {
      const input = this.viewToggle.querySelector(
        `input[name="view"][value="${savedView}"]`
      );
      if (input) input.checked = true;
    }

    // Changement de view
    this.viewToggle.querySelectorAll("input[name='view']").forEach(input => {
      input.addEventListener("change", () => {
        localStorage.setItem("vehicleView", input.value); // sauvegarde locale
        this.submitFilters(); // recharge via AJAX
      });
    });
  }

  // --- Initialisation des événements ---
  initEvents() {
    // Changement de filtres (checkbox, select)
    this.form.addEventListener("change", e => {
      if (!e.target.matches("input, select")) return;
      this.submitFilters();
    });

    // Pagination
    document.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;
      e.preventDefault();
      const page = Number.parseInt(btn.dataset.page);
      if (!isNaN(page)) this.submitFilters(page);
    });

    // Suppression badges
    if (this.summaryContainer) {
      this.summaryContainer.addEventListener("click", e => {
        if (!e.target.matches(".badge-remove")) return;

        const filter = e.target.dataset.filter;
        const value = e.target.dataset.value;

        // Reset slider si applicable
        const slider = this.form.querySelector(
          `.double-slider[data-filter="${filter}"]`
        );
        if (slider && typeof slider.resetSlider === "function")
          slider.resetSlider();
        else {
          // Reset checkbox
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

  // --- Envoi AJAX ---
  async submitFilters(page = 1) {
    const formData = new FormData(this.form);
    const filters = {};

    // Construction objet filters
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

    // Ajout de la view depuis toggle
    const viewInput = this.viewToggle.querySelector(
      "input[name='view']:checked"
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
      console.error("Erreur AJAX :", err);
    }
  }

  // --- Mise à jour du DOM ---
  updateDOM(data) {
    if (!this.resultsContainer) return;

    // Remplace entièrement le container principal par le HTML rendu côté serveur
    if (data.results) this.resultsContainer.innerHTML = data.results;

    // Met à jour les badges
    if (this.badges) this.badges.updateBadges();
  }
}

// --- Initialisation automatique ---
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
