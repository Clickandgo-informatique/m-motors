// assets/js/FetchForm.js

export default class FetchForm {
  constructor(input) {
    if (!input) return;

    this.input = input;

    // =========================
    // Containers
    // =========================
    this.gridContainer = document.getElementById("vehicles-search-results");

    this.paginationTop = document.querySelector(
      '[data-target="pagination-top"]'
    );

    this.paginationBottom = document.querySelector(
      '[data-target="pagination-bottom"]'
    );

    // =========================
    // URL AJAX
    // =========================
    this.url = input.form?.action || "";

    // =========================
    // State
    // =========================
    this.timeout = null;
    this.controller = null;
    this.requestId = 0;

    // =========================
    // Events
    // =========================
    this.input.addEventListener("input", e => this.onInput(e));

    this.bindFilters();
    this.bindExternalForms();
    this.bindViewSwitch();

    console.log("[FetchForm] initialized");
  }

  // =========================
  // SEARCH VALUE
  // =========================
  getSearchValue() {
    return this.input?.value?.trim() || "";
  }

  // =========================
  // VIEW VALUE
  // =========================
  getViewValue() {
    const checked = document.querySelector('input[name="view"]:checked');
    return checked ? checked.value : "grid";
  }

  // =========================
  // FILTERS
  // =========================
  bindFilters() {
    const form = this.input.form;
    if (!form) return;

    form.querySelectorAll("input, select").forEach(el => {
      if (el === this.input) return;

      el.addEventListener("change", () => {
        this.fetchResults(this.getSearchValue());
      });
    });
  }

  // =========================
  // EXTERNAL FORMS
  // =========================
  bindExternalForms() {
    const externalForm = document.querySelector("#view-switch-form");
    if (!externalForm) return;

    externalForm.querySelectorAll('input[name="view"]').forEach(input => {
      input.addEventListener("change", () => {
        this.fetchResults(this.getSearchValue());
      });
    });
  }

  // =========================
  // VIEW SWITCH (IMPORTANT FIX)
  // Event delegation pour éviter perte après AJAX
  // =========================
  bindViewSwitch() {
    document.addEventListener("change", e => {
      if (!e.target.matches('input[name="view"]')) return;

      console.log("[FetchForm] view changed:", e.target.value);

      this.fetchResults(this.getSearchValue());
    });
  }

  // =========================
  // INPUT SEARCH
  // =========================
  onInput(e) {
    clearTimeout(this.timeout);

    const value = (e.target.value || "").trim();

    this.timeout = setTimeout(() => {
      this.fetchResults(value);
    }, 300);
  }

  // =========================
  // FETCH CORE
  // =========================
  async fetchResults(query) {
    if (!this.url) {
      console.warn("[FetchForm] No URL defined");
      return;
    }

    const currentRequest = ++this.requestId;

    try {
      if (this.controller) {
        this.controller.abort();
      }

      this.controller = new AbortController();

      const form = this.input.form;
      const formData = new FormData(form);

      formData.set("q", query);
      formData.set("filters[view]", this.getViewValue());
      formData.set("mode", "search");

      const params = new URLSearchParams(formData);

      const response = await fetch(`${this.url}?${params.toString()}`, {
        signal: this.controller.signal,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          Accept: "application/json"
        }
      });

      if (!response.ok) throw new Error(response.status);

      const data = await response.json();

      if (currentRequest !== this.requestId) return;

      this.render(data);
    } catch (err) {
      if (err.name !== "AbortError") {
        console.error("[FetchForm]", err);
      }
    }
  }

  // =========================
  // RENDER
  // =========================
  render(data) {
    if (!data) return;

    if (this.gridContainer && data.results !== undefined) {
      this.gridContainer.innerHTML = data.results;
    }

    if (this.paginationTop && data.paginationTop !== undefined) {
      this.paginationTop.innerHTML = data.paginationTop;
    }

    if (this.paginationBottom && data.paginationBottom !== undefined) {
      this.paginationBottom.innerHTML = data.paginationBottom;
    }
  }
}
