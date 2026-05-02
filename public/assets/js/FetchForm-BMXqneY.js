export default class FetchForm {
  constructor(input) {
    if (!input) return;

    // Protection anti double instance (cause réelle de ton bug)
    if (window.__fetchFormInit) return;
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

    this.currentPage = 1;
    this.timeout = null;
    this.controller = null;
    this.requestId = 0;

    this.init();
  }

  init() {
    this.input.addEventListener("input", e => this.onInput(e));
    this.bindFilters();
    this.bindViewSwitch();
    this.bindPagination();
  }

  onInput(e) {
    clearTimeout(this.timeout);

    this.timeout = setTimeout(() => {
      this.currentPage = 1;
      this.fetchResults(e.target.value.trim());
    }, 300);
  }

  bindFilters() {
    this.form?.querySelectorAll("input, select").forEach(el => {
      if (el === this.input) return;

      el.addEventListener("change", () => {
        this.currentPage = 1;
        this.fetchResults(this.input.value.trim());
      });
    });
  }

  bindViewSwitch() {
    document
      .getElementById("view-switch-form")
      ?.addEventListener("change", () => {
        this.currentPage = 1;
        this.fetchResults(this.input.value.trim());
      });
  }

  bindPagination() {
    document.addEventListener("click", e => {
      const link = e.target.closest(".pagination a");
      if (!link) return;

      e.preventDefault();

      const page = parseInt(link.dataset.page || "1");

      this.currentPage = page;

      this.fetchResults(this.getSearchValue());
    });
  }
  async fetchResults(query) {
    const requestId = ++this.requestId;

    try {
      if (this.controller) this.controller.abort();

      this.controller = new AbortController();

      const formData = new FormData(this.form);

      formData.set("q", query);
      formData.set("page", this.currentPage);

      const params = new URLSearchParams(formData);

      const res = await fetch(`${this.url}?${params}`, {
        signal: this.controller.signal,
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      const data = await res.json();

      if (requestId !== this.requestId) return;

      this.render(data);
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error(e);
      }
    }
  }

  render(data) {
    if (this.gridContainer && data.results) {
      this.gridContainer.innerHTML = data.results;
    }

    if (this.paginationTop) {
      this.paginationTop.innerHTML = data.paginationTop || "";
    }

    if (this.paginationBottom) {
      this.paginationBottom.innerHTML = data.paginationBottom || "";
    }
  }
}
