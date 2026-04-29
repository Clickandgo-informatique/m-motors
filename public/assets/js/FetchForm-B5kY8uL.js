export default class FetchForm {
  constructor(input) {
    if (!input) return;

    this.input = input;
    const form = input.form;

    this.url = form?.action || "";

    this.resultDiv = document.getElementById(input.dataset.resultDiv);
    this.gridContainer = document.getElementById("vehicles-search-results");
    this.paginationTop = document.querySelector('[data-target="pagination-top"]');
    this.paginationBottom = document.querySelector('[data-target="pagination-bottom"]');

    this.timeout = null;
    this.controller = null;

    // 🔥 UNIQUE ENTRY POINT
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      this.fetchAll();
    });

    // input autocomplete
    this.input.addEventListener("input", () => {
      clearTimeout(this.timeout);

      this.timeout = setTimeout(() => {
        this.fetchAll();
      }, 300);
    });

    // filters
    form.querySelectorAll("input, select").forEach(el => {
      if (el !== this.input) {
        el.addEventListener("change", () => {
          this.fetchAll();
        });
      }
    });

    console.log("[FetchForm] ready");
  }

  async fetchAll() {
    if (!this.url) return;

    try {
      // kill previous request
      if (this.controller) {
        this.controller.abort();
      }

      this.controller = new AbortController();

      const form = this.input.form;
      const formData = new FormData(form);

      // 🔥 FORCING SEARCH VALUE CONSISTENCY
      formData.set("q", this.input.value || "");

      const params = new URLSearchParams(formData);

      const response = await fetch(`${this.url}?${params.toString()}`, {
        signal: this.controller.signal,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          Accept: "application/json"
        }
      });

      const data = await response.json();

      this.render(data);

    } catch (e) {
      if (e.name !== "AbortError") {
        console.error(e);
      }
    }
  }

  render(data) {
    if (!data) return;

    // =========================
    // 1. AUTOCOMPLETE
    // =========================
    if (this.resultDiv && Array.isArray(data.items)) {
      this.resultDiv.innerHTML = "";

      data.items.forEach(item => {
        const el = document.createElement("div");
        el.className = "item";
        el.textContent = item.label;

        el.addEventListener("click", () => {
          this.input.value = item.label;
          this.resultDiv.innerHTML = "";
          this.fetchAll();
        });

        this.resultDiv.appendChild(el);
      });
    }

    // =========================
    // 2. GRID
    // =========================
    if (this.gridContainer && data.results !== undefined) {
      this.gridContainer.innerHTML = data.results;
    }

    // =========================
    // 3. PAGINATION TOP
    // =========================
    if (this.paginationTop && data.paginationTop !== undefined) {
      this.paginationTop.innerHTML = data.paginationTop;
    }

    // =========================
    // 4. PAGINATION BOTTOM
    // =========================
    if (this.paginationBottom && data.paginationBottom !== undefined) {
      this.paginationBottom.innerHTML = data.paginationBottom;
    }
  }
}