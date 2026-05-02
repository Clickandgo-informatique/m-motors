import FilterBadges from "./FilterBadges.js";
import initDoubleSlider from "./rangeSelector.js";
import Autocomplete from "./Autocomplete.js";

export default class VehiclesFilter {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    // Anti double init
    if (form.dataset.vehiclesFilterInit === "1") return;
    form.dataset.vehiclesFilterInit = "1";

    this.form = form;
    this.url = form.dataset.fetchUrl;

    if (!this.url) return;

    this.container = document.querySelector("#vehicles-results");

    this.resultsEl = document.querySelector(
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

    if (!this.container || !this.resultsEl) {
      console.warn("VehiclesFilter: DOM incomplet");
      return;
    }

    this.loading = false;

    if (this.summaryContainer) {
      this.badges = new FilterBadges(
        this.summaryContainer,
        this.form,
        this.submitFilters.bind(this)
      );
    }

    this.initSliders();
    this.initEvents();
    this.initAutocomplete();
    this.initCardsClick();
    this.initViewSwitcher();
  }

  initSliders() {
    const sliders = this.form.querySelectorAll(".double-slider");

    if (!sliders.length) return;

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

  initEvents() {
    if (this.eventsBound) return;
    this.eventsBound = true;

    // IMPORTANT : event global délégué (corrige ton bug principal)
    document.addEventListener("change", e => {
      if (!e.target.closest("#filters-form")) return;
      if (!e.target.matches("input, select")) return;

      this.submitFilters();
    });

    // Pagination
    document.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;

      e.preventDefault();

      const page = parseInt(btn.dataset.page, 10);
      if (!isNaN(page)) {
        this.submitFilters(page);
      }
    });

    // Badges suppression
    if (this.summaryContainer) {
      this.summaryContainer.addEventListener("click", e => {
        const btn = e.target.closest(".badge-remove");
        if (!btn) return;

        const filter = btn.dataset.filter;
        const value = btn.dataset.value;

        const checkboxes = this.form.querySelectorAll(
          `input[name="filters[${filter}][]"]`
        );

        checkboxes.forEach(cb => {
          if (cb.value === value) cb.checked = false;
        });

        if (this.badges) {
          this.badges.updateBadges();
        }

        this.submitFilters();
      });
    }
  }

  initViewSwitcher() {
    const inputs = document.querySelectorAll("input[name='view']");

    inputs.forEach(input => {
      input.addEventListener("change", () => {
        this.submitFilters(1);
      });
    });
  }

  async submitFilters(page = 1) {
    if (this.loading) return;
    this.loading = true;

    try {
      const formData = new FormData(this.form);
      const filters = {};

      for (const [key, value] of formData.entries()) {
        const match = key.match(/^filters\[(.+?)\](\[\])?$/);
        if (!match) continue;

        const name = match[1];
        const isArray = !!match[2];

        if (!filters[name]) {
          filters[name] = isArray ? [] : null;
        }

        if (isArray) {
          filters[name].push(value);
        } else {
          filters[name] = value;
        }
      }

      const viewInput = document.querySelector("input[name='view']:checked");

      if (viewInput) {
        filters.view = viewInput.value;
      }

      const res = await fetch(this.url, {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({ filters, page })
      });

      const data = await res.json();

      if (this.resultsEl) {
        this.resultsEl.innerHTML =
          data.results && data.results.trim() !== ""
            ? data.results
            : "<div class='text-center text-muted'>Aucun véhicule trouvé</div>";
      }

      if (this.paginationTop) {
        this.paginationTop.innerHTML = data.paginationTop || "";
      }

      if (this.paginationBottom) {
        this.paginationBottom.innerHTML = data.paginationBottom || "";
      }

      if (this.badges) {
        this.badges.updateBadges();
      }

      this.initAutocomplete();

      window.dispatchEvent(new Event("ui:updated"));
    } catch (err) {
      console.error("VehiclesFilter error:", err);
    } finally {
      this.loading = false;
    }
  }

  initAutocomplete() {
    this.form.querySelectorAll("[data-autocomplete]").forEach(input => {
      if (input.dataset.autocompleteInitialized === "1") return;

      new Autocomplete(input);
      input.dataset.autocompleteInitialized = "1";
    });
  }

  initCardsClick() {
    const container = this.resultsEl || document;

    container.addEventListener("click", e => {
      const card = e.target.closest(".vehicle-item[data-url]");
      if (!card) return;

      const url = card.dataset.url;
      if (!url) return;

      window.location.href = url;
    });
  }
}
