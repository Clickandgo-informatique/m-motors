export default class FetchForm {
  constructor(input) {
    if (!input) return;

    this.input = input;
    const dataset = input.dataset || {};

    // =========================
    // Containers
    // =========================
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

    // =========================
    // URL
    // =========================
    this.url = input.form?.action || "";

    // =========================
    // State control (IMPORTANT)
    // =========================
    this.timeout = null;
    this.controller = null;
    this.requestId = 0;

    // =========================
    // Events
    // =========================
    this.input.addEventListener("input", e => this.onInput(e));

    // 🔥 UNIQUE ENTRY POINT FOR FILTERS
    this.bindFilters();

    console.log("[FetchForm] initialized");
  }

  // =========================================================
  // FILTER BINDING (checkbox/select/etc.)
  // =========================================================
  bindFilters() {
    const form = this.input.form;
    if (!form) return;

    form.querySelectorAll("input, select").forEach(el => {
      if (el === this.input) return;

      el.addEventListener("change", () => {
        this.fetchResults(this.input.value || "");
      });
    });
  }

  // =========================================================
  // INPUT (autocomplete)
  // =========================================================
  onInput(e) {
    clearTimeout(this.timeout);

    const value = (e.target.value || "").trim();

    this.timeout = setTimeout(() => {
      this.fetchResults(value);
    }, 300);
  }

  // =========================================================
  // FETCH CORE (single source of truth)
  // =========================================================
  async fetchResults(query) {
    if (!this.url) return;

    const currentRequest = ++this.requestId;

    try {
      // abort previous request
      if (this.controller) {
        this.controller.abort();
      }

      this.controller = new AbortController();

      const form = this.input.form;
      const formData = new FormData(form);

      // force sync search term
      formData.set("q", query);

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

      // ignore outdated responses (CRITICAL FIX)
      if (currentRequest !== this.requestId) return;

      this.render(data);
    } catch (err) {
      if (err.name !== "AbortError") {
        console.error("[FetchForm]", err);
      }
    }
  }

  // =========================================================
  // RENDER (3 synchronized sections)
  // =========================================================
  render(data) {
    if (!data) return;

    // =========================
    // AUTOCOMPLETE
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
          this.fetchResults(item.label);
        });

        this.resultDiv.appendChild(el);
      });
    }

    // =========================
    // GRID RESULTS
    // =========================
    if (this.gridContainer && data.results !== undefined) {
      this.gridContainer.innerHTML = data.results;
    }

    // =========================
    // PAGINATION TOP
    // =========================
    if (this.paginationTop && data.paginationTop !== undefined) {
      this.paginationTop.innerHTML = data.paginationTop;
    }

    // =========================
    // PAGINATION BOTTOM
    // =========================
    if (this.paginationBottom && data.paginationBottom !== undefined) {
      this.paginationBottom.innerHTML = data.paginationBottom;
    }
  }
}
