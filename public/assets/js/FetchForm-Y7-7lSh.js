// assets/js/FetchForm.js

export default class FetchForm {
  constructor(input) {
    if (!input) return;

    this.input = input;

    this.gridContainer = document.getElementById("vehicles-search-results");

    this.paginationTop = document.querySelector(
      '[data-target="pagination-top"]'
    );

    this.paginationBottom = document.querySelector(
      '[data-target="pagination-bottom"]'
    );

    this.url = input.form?.action || "";

    this.timeout = null;
    this.controller = null;
    this.requestId = 0;

    this.input.addEventListener("input", e => this.onInput(e));

    this.bindFilters();
    this.bindExternalForms();

    console.log("[FetchForm] initialized");
  }

  getSearchValue() {
    return this.input?.value?.trim() || "";
  }

  getViewValue() {
    return (
      document.querySelector('#view-switch-form input[name="view"]:checked')
        ?.value || "grid"
    );
  }

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

  bindExternalForms() {
    const externalForm = document.querySelector("#view-switch-form");
    if (!externalForm) return;

    externalForm.querySelectorAll('input[name="view"]').forEach(input => {
      input.addEventListener("change", () => {
        this.fetchResults(this.getSearchValue());
      });
    });
  }

  onInput(e) {
    clearTimeout(this.timeout);

    const value = (e.target.value || "").trim();

    this.timeout = setTimeout(() => {
      this.fetchResults(value);
    }, 400);
  }

  async fetchResults(query) {
    if (!this.url) return;

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
