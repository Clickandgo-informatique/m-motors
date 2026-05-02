export default class FetchForm {
  constructor(input) {
    if (!input) return;

    // console.log("[FetchForm] INIT");

    // 🔴 protection contre double instanciation (cause probable de ton bug)
    if (window.__fetchFormInit) {
      // console.warn("[FetchForm] already initialized");
      return;
    }
    window.__fetchFormInit = true;

    this.input = input;
    this.form = input.form;

    this.url = this.form?.action || "";

    this.gridContainer = document.getElementById("vehicles-search-results");
    this.paginationTop = document.querySelector(
      '[data-target="pagination-top"]'
    );
    this.paginationBottom = document.querySelector(
      '[data-target="pagination-bottom"]'
    );

    this.requestId = 0;
    this.controller = null;
    this.timeout = null;

    this.currentPage = 1;

    this.init();
  }

  init() {
    this.input.addEventListener("input", e => this.onInput(e));

    this.bindFilters();
    this.bindViewSwitch();
    this.bindPagination();
  }

  getSearchValue() {
    return this.input?.value?.trim() || "";
  }

  getViewValue() {
    const checked = document.querySelector('input[name="view"]:checked');
    return checked ? checked.value : "grid";
  }

  onInput(e) {
    clearTimeout(this.timeout);

    const value = (e.target.value || "").trim();

    this.timeout = setTimeout(() => {
      this.currentPage = 1;
      this.fetchResults(value);
    }, 300);
  }

  bindFilters() {
    if (!this.form) return;

    this.form.querySelectorAll("input, select").forEach(el => {
      if (el === this.input) return;

      el.addEventListener("change", () => {
        this.currentPage = 1;
        this.fetchResults(this.getSearchValue());
      });
    });
  }

  bindViewSwitch() {
    const switchForm = document.getElementById("view-switch-form");

    if (!switchForm) return;

    switchForm.addEventListener("change", e => {
      if (!e.target.matches('input[name="view"]')) return;

      this.currentPage = 1;
      this.fetchResults(this.getSearchValue());
    });
  }

  bindPagination() {
    document.addEventListener("click", e => {
      const link = e.target.closest(".pagination a");

      if (!link) return;

      e.preventDefault();

      const url = new URL(link.href);
      const page = parseInt(url.searchParams.get("page") || "1");

      this.currentPage = page;

      this.fetchResults(this.getSearchValue());
    });
  }

  async fetchResults(query) {
    if (!this.url) return;

    const currentRequest = ++this.requestId;

    try {
      if (this.controller) {
        this.controller.abort();
      }

      this.controller = new AbortController();

      const formData = new FormData(this.form);

      formData.set("q", query);
      formData.set("filters[view]", this.getViewValue());
      formData.set("mode", "search");
      formData.set("page", this.currentPage);

      const params = new URLSearchParams(formData);

      const response = await fetch(`${this.url}?${params.toString()}`, {
        signal: this.controller.signal,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          Accept: "application/json"
        }
      });

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
    // console.log("RENDER CALLED");

    // console.log(
    //   "CONTAINERS COUNT:",
    //   document.querySelectorAll("#vehicles-search-results").length
    // );

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
