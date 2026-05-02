import FilterBadges from "./FilterBadges.js";
import initDoubleSlider from "./rangeSelector.js";
import Autocomplete from "./Autocomplete.js";

export default class VehiclesFilter {
  constructor(form) {
    console.log("DEBUG DOM CHECK:", {
      container: document.querySelector("#vehicles-results"),
      results: document.querySelector(
        '[data-target="vehicles-search-results"]'
      ),
      paginationTop: document.querySelector('[data-target="pagination-top"]'),
      paginationBottom: document.querySelector(
        '[data-target="pagination-bottom"]'
      )
    });
    if (!(form instanceof HTMLFormElement)) return;

    if (form.dataset.initialized === "1") return;
    form.dataset.initialized = "1";

    this.form = form;
    this.url = form.dataset.fetchUrl;

    if (!this.url) return;

    this.container = document.querySelector("#vehicles-results");
    if (!this.container) {
      console.warn("VehiclesFilter: container introuvable");
      return;
    }

    this.resultsEl = this.container.querySelector(
      '[data-target="vehicles-search-results"]'
    );

    if (!this.resultsEl) {
      console.warn("VehiclesFilter: results introuvables");
      return;
    }

    this.loading = false;

    this.summaryContainer = this.container.querySelector(
      '[data-target="filters-summary"]'
    );

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
    this.initViewSwitcher();
    this.initCardsClick();
  }

  initSliders() {
    const sliders = this.form.querySelectorAll(".double-slider");

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

    this.form.addEventListener("change", e => {
      if (!e.target.matches("input, select")) return;
      this.submitFilters();
    });

    this.container.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;

      e.preventDefault();
      this.submitFilters(parseInt(btn.dataset.page, 10));
    });
  }

  initViewSwitcher() {
    this.form.querySelectorAll("input[name='view']").forEach(input => {
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

      const viewInput = this.form.querySelector("input[name='view']:checked");
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

      this.resultsEl.innerHTML =
        data.results?.trim() || "<div>Aucun véhicule trouvé</div>";

      window.dispatchEvent(new Event("ui:updated"));

      if (this.badges) {
        this.badges.updateBadges();
      }
    } catch (e) {
      console.error("VehiclesFilter error:", e);
    } finally {
      this.loading = false;
    }
  }

  initAutocomplete() {
    this.form.querySelectorAll("[data-autocomplete]").forEach(input => {
      if (input.dataset.initialized === "1") return;

      input.dataset.initialized = "1";
      new Autocomplete(input);
    });
  }

  initCardsClick() {
    this.container.addEventListener("click", e => {
      const card = e.target.closest(".vehicle-card[data-item-link]");
      if (!card) return;

      window.location.href = card.dataset.itemLink;
    });
  }
}
