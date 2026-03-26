// --- vehiclesFilters.js ---
import FilterBadges from "./FilterBadges.js";
import initDoubleSlider from "./rangeSelector.js";

export default class VehiclesFilter {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.url = form.dataset.fetchUrl; // URL AJAX côté controller
    if (!this.url) return;

    // Conteneurs principaux
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

    // --- INIT BADGES ---
    if (this.summaryContainer) {
      this.badges = new FilterBadges(
        this.summaryContainer,
        this.form,
        this.submitFilters.bind(this)
      );
    }

    // --- INIT SLIDERS ---
    this.initSliders();

    // --- INIT TOGGLE VIEW ---
    this.initViewToggle();

    // --- INIT EVENTS ---
    this.initEvents();
  }

  // --- Double sliders ---
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

  // --- Toggle radio view ---
  initViewToggle() {
    this.viewToggle = document.querySelector("#view-switch-form");
    if (!this.viewToggle) return;

    // Lecture valeur persistée côté client (localStorage)
    const savedView = localStorage.getItem("vehicleView");
    if (savedView) {
      const input = this.viewToggle.querySelector(
        `input[name="view"][value="${savedView}"]`
      );
      if (input) input.checked = true;
    }

    // Événement changement toggle
    this.viewToggle.querySelectorAll("input[name='view']").forEach(input => {
      input.addEventListener("change", () => {
        localStorage.setItem("vehicleView", input.value); // sauvegarde locale
        this.submitFilters(); // recharge les résultats AJAX
      });
    });
  }

  // --- Events form, pagination, badges ---
  initEvents() {
    // Changement sur filtres (checkbox, select, sliders)
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

  // --- AJAX submit ---
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
      } else filters[name] = value;
    }

    // --- Ajout view depuis toggle ---
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

  // --- Mise à jour DOM ---
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

// --- Observer pour initialisation automatique ---
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
