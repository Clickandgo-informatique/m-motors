export default class FetchForm {
  constructor(input) {
    if (!input) return;

    this.input = input;
    const dataset = input.dataset || {};

    this.resultDiv = dataset.resultDiv
      ? document.getElementById(dataset.resultDiv)
      : null;

    this.gridContainer = document.getElementById("vehicles-search-results");
    this.paginationTop = document.querySelector(
      '[data-target="pagination-top"]'
    );
    this.paginationBottom = document.querySelector(
      '[data-target="pagination-bottom"]'
    );

    this.url = dataset.fetchUrl || input.form?.action || "";

    this.timeout = null;
    this.controller = null;

    // ❗ UNIQUE SOURCE ONLY
    this.input.addEventListener("input", e => this.onInput(e));

    console.log("[FetchForm] stable version");
  }

  onInput(e) {
    clearTimeout(this.timeout);

    const value = (e.target.value || "").trim();

    this.timeout = setTimeout(() => {
      this.fetchResults(value);
    }, 300);
  }

  async fetchResults(query) {
    if (!this.url) return;

    try {
      if (this.controller) this.controller.abort();
      this.controller = new AbortController();

      const form = this.input.form;
      const formData = new FormData(form);

      formData.set("q", query);

      const params = new URLSearchParams(formData);

      const response = await fetch(`${this.url}?${params.toString()}`, {
        signal: this.controller.signal,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          Accept: "application/json"
        }
      });

      const data = await response.json();

      this.renderResults(data);
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error(e);
      }
    }
  }

  renderResults(data) {
    if (!data) return;

    if (this.resultDiv && Array.isArray(data.items)) {
      this.resultDiv.innerHTML = "";
      data.items.forEach(item => {
        const el = document.createElement("div");
        el.textContent = item.label;
        this.resultDiv.appendChild(el);
      });
    }

    if (this.gridContainer && data.results) {
      this.gridContainer.innerHTML = data.results;
    }

    if (this.paginationTop && data.paginationTop) {
      this.paginationTop.innerHTML = data.paginationTop;
    }

    if (this.paginationBottom && data.paginationBottom) {
      this.paginationBottom.innerHTML = data.paginationBottom;
    }
  }
}
