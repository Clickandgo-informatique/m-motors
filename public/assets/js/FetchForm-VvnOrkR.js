export default class FetchForm {
  constructor(input) {
    if (!input) return;

    this.input = input;
    const dataset = input.dataset || {};

    // --- Containers ---
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

    // --- URL ---
    this.url = dataset.fetchUrl || input.form?.action || "";

    // --- Options ---
    this.debounceDelay = 300;
    this.timeout = null;

    // --- Abort control (évite les conflits de requêtes) ---
    this.controller = null;

    // --- Events ---
    this.input.addEventListener("input", e => this.onInput(e));

    // IMPORTANT : déclenche aussi sur filtres (checkbox/select)
    this.bindFormFilters();

    console.log("[FetchForm] initialisé");
  }

  bindFormFilters() {
    const form = this.input.form;
    if (!form) return;

    form.querySelectorAll("input, select").forEach(el => {
      if (el === this.input) return;

      el.addEventListener("change", () => {
        this.fetchResults(this.input.value || "");
      });
    });
  }

  onInput(e) {
    clearTimeout(this.timeout);

    const value = (e.target.value || "").trim();

    this.timeout = setTimeout(() => {
      this.fetchResults(value);
    }, this.debounceDelay);
  }

  async fetchResults(query) {
    if (!this.url) return;

    try {
      // ❌ stop requête précédente
      if (this.controller) {
        this.controller.abort();
      }

      this.controller = new AbortController();

      const form = this.input.form;
      const formData = new FormData(form);

      // 🔥 force search term cohérent backend
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

      this.renderResults(data);
    } catch (err) {
      if (err.name !== "AbortError") {
        console.error("[FetchForm] fetch error:", err);
      }
    }
  }

  renderResults(data) {
    if (!data) return;

    // =========================================================
    // AUTOCOMPLETE ITEMS
    // =========================================================
    if (Array.isArray(data.items) && this.resultDiv) {
      this.renderItems(data.items);
    }

    // =========================================================
    // GRID (résultats véhicules)
    // =========================================================
    if (this.gridContainer && data.results) {
      this.gridContainer.innerHTML = data.results;
    }

    // =========================================================
    // PAGINATION TOP
    // =========================================================
    if (this.paginationTop && data.paginationTop) {
      this.paginationTop.innerHTML = data.paginationTop;
    }

    // =========================================================
    // PAGINATION BOTTOM
    // =========================================================
    if (this.paginationBottom && data.paginationBottom) {
      this.paginationBottom.innerHTML = data.paginationBottom;
    }

    // =========================================================
    // NO RESULTS FALLBACK
    // =========================================================
    if (!data.items && !data.results && this.resultDiv) {
      this.resultDiv.innerHTML = `<div class="no-results">Aucun résultat</div>`;
    }
  }

  renderItems(items) {
    if (!this.resultDiv) return;

    this.resultDiv.innerHTML = "";

    items.forEach(item => {
      const el = document.createElement("div");
      el.className = "item";
      el.textContent = item.label;

      el.addEventListener("click", () => {
        // injecte proprement sans casser le flow
        this.input.value = item.label;

        // ferme autocomplete
        this.resultDiv.innerHTML = "";

        // relance recherche complète (filters + pagination)
        this.fetchResults(item.label);
      });

      this.resultDiv.appendChild(el);
    });
  }
}
