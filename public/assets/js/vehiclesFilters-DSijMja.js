// assets/js/vehiclesFilters.js
import FilterBadges from "./FilterBadges.js";
import initDoubleSlider from "./rangeSelector.js";
import Autocomplete from "./Autocomplete.js";

export default class VehiclesFilter {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    // Anti double init
    if (form._vehiclesFilterInstance) return;
    form._vehiclesFilterInstance = this;

    this.form = form;
    this.url = form.dataset.fetchUrl;

    if (!this.url) return;

    this.mainForm = document.querySelector("#filters-form") || this.form;

    this.container =
      document.querySelector("#vehicles-results") ||
      document.querySelector("#vehicles-container");

    this.resultsEl = this.container?.querySelector(
      '[data-target="vehicles-search-results"]'
    );

    this.paginationTop = this.container?.querySelector(
      '[data-target="pagination-top"]'
    );

    this.paginationBottom = this.container?.querySelector(
      '[data-target="pagination-bottom"]'
    );

    this.summaryContainer = this.container?.querySelector(
      '[data-target="filters-summary"]'
    );

    if (!this.container || !this.resultsEl) {
      console.warn("VehiclesFilter : container introuvable");
      return;
    }

    if (this.summaryContainer && this.mainForm.matches("#filters-form")) {
      this.badges = new FilterBadges(
        this.summaryContainer,
        this.mainForm,
        this.submitFilters.bind(this)
      );
    }

    if (this.mainForm.matches("#filters-form")) {
      this.initSliders();
    }

    this.initEvents();
    this.initAutocomplete();
    this.initCardsClick();
  }

  initSliders() {
    const sliders = this.mainForm.querySelectorAll(".double-slider");
    if (!sliders.length || typeof initDoubleSlider !== "function") return;

    sliders.forEach(slider => {
      initDoubleSlider(slider);

      let timer = null;

      slider.addEventListener("sliderChanged", e => {
        const { filter, min, max } = e.detail;

        const inputMin = this.mainForm.querySelector(
          `input[name="filters[${filter}Min]"]`
        );

        const inputMax = this.mainForm.querySelector(
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

    this.mainForm.addEventListener("change", e => {
      if (
        !e.target.matches("input[type='checkbox'], select, input[type='radio']")
      ) {
        return;
      }

      this.submitFilters();
    });

    if (this.form !== this.mainForm) {
      this.form.addEventListener("change", e => {
        if (e.target.name === "view") {
          this.submitFilters();
        }
      });
    }

    this.container.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;

      e.preventDefault();

      const page = Number.parseInt(btn.dataset.page);
      if (!isNaN(page)) this.submitFilters(page);
    });

    if (this.badges) {
      this.summaryContainer.addEventListener("click", e => {
        if (!e.target.matches(".badge-remove")) return;

        const filter = e.target.dataset.filter;
        const value = e.target.dataset.value;

        const checkboxes = this.mainForm.querySelectorAll(
          `input[name="filters[${filter}][]"]`
        );

        checkboxes.forEach(cb => {
          if (cb.value === value) cb.checked = false;
        });

        this.badges.updateBadges();
        this.submitFilters();
      });
    }
  }

  async submitFilters(page = 1) {
    try {
      const formData = new FormData(this.mainForm);
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
      if (viewInput) filters.view = viewInput.value;

      const res = await fetch(this.url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters, page })
      });

      const data = await res.json();

      if (this.resultsEl) {
        this.resultsEl.innerHTML =
          data.results && data.results.trim() !== ""
            ? data.results
            : "<div class='text-center text-muted'>Aucun véhicule trouvé</div>";
      }

      if (this.paginationTop && data.paginationTop) {
        this.paginationTop.innerHTML = data.paginationTop;
      }

      if (this.paginationBottom && data.paginationBottom) {
        this.paginationBottom.innerHTML = data.paginationBottom;
      }

      if (this.badges) this.badges.updateBadges();

      this.initAutocomplete();
    } catch (err) {
      console.error("Erreur AJAX :", err);
    }
  }

  initAutocomplete() {
    this.mainForm.querySelectorAll("[data-autocomplete]").forEach(input => {
      if (!input.dataset.autocompleteInitialized) {
        new Autocomplete(input);
        input.dataset.autocompleteInitialized = "true";
      }
    });
  }

  initCardsClick() {
    if (this.cardsClickBound) return;
    this.cardsClickBound = true;

    const container = this.resultsEl || document;

    container.addEventListener("click", e => {
      const card = e.target.closest(".vehicle-card[data-item-link]");
      if (!card) return;

      const url = card.dataset.itemLink;
      if (!url) return;

      if (
        window.AjaxManagerInstance &&
        typeof window.AjaxManagerInstance.loadModal === "function"
      ) {
        window.AjaxManagerInstance.loadModal(url);
      } else {
        window.location.href = url;
      }
    });
  }

  loadSidebarFilters() {
    const container = document.querySelector("#sidebar-filters");

    if (!container || !container.dataset.url) return;

    fetch(container.dataset.url)
      .then(res => res.text())
      .then(html => {
        container.innerHTML = html;
      })
      .catch(err => {
        console.error("Erreur chargement sidebar filters", err);
      });
  }
}

/**
 * INIT GLOBAL SAFE
 */
function watchFetchForms() {
  const initForm = form => {
    if (form._vehiclesFilterInstance) return;
    new VehiclesFilter(form);
  };

  document.querySelectorAll("[data-fetch-form]").forEach(initForm);

  const observer = new MutationObserver(mutations => {
    mutations.forEach(mutation => {
      mutation.addedNodes.forEach(node => {
        if (!(node instanceof HTMLElement)) return;

        if (node.matches?.("[data-fetch-form]")) initForm(node);

        node.querySelectorAll?.("[data-fetch-form]").forEach(initForm);
      });
    });
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true
  });
}

document.addEventListener("DOMContentLoaded", watchFetchForms);
