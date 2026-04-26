/**
 * FetchForm
 * ----------
 * Version corrigée :
 * - support JSON (OBLIGATOIRE)
 * - séparation items / grid / pagination
 * - plus de response.text() (cause principale du bug)
 * - plus de mélange DOM dans un seul container
 */

export default class FetchForm {
  constructor(input) {
    if (!input) return;

    this.input = input;
    const dataset = input.dataset || {};

    // --- Containers ---
    this.resultDivId = dataset.resultDiv || null;
    this.resultDiv = this.resultDivId
      ? document.getElementById(this.resultDivId)
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
    this.itemClass = dataset.itemClass || "item";
    this.linkClass = dataset.linkClass || "link";
    this.noResultsClass = dataset.noResultsClass || "no-results";

    this.resultLinks = dataset.resultLinks === "true";
    this.highlight = dataset.highlight === "true";

    // --- Debounce ---
    this.debounceDelay = 300;
    this.timeout = null;

    // --- Events ---
    this.input.addEventListener("input", e => this.onInput(e));

    console.log(`[FetchForm] initialisé`);
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
      const params = new URLSearchParams({
        [this.input.name]: query
      });

      const response = await fetch(`${this.url}?${params.toString()}`, {
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          Accept: "application/json"
        }
      });

      if (!response.ok) throw new Error(response.status);

      // IMPORTANT : JSON UNIQUEMENT
      const data = await response.json();

      this.renderResults(data);
    } catch (err) {
      console.error("[FetchForm] fetch error:", err);
    }
  }

  renderResults(data) {
    if (!data) return;

    // =========================================================
    // 1. AUTOCOMPLETE ITEMS
    // =========================================================
    if (Array.isArray(data.items) && this.resultDiv) {
      this.renderItems(data.items);
    }

    // =========================================================
    // 2. GRID VEHICLES
    // =========================================================
    if (data.results && this.gridContainer) {
      this.gridContainer.innerHTML = data.results;
    }

    // =========================================================
    // 3. PAGINATION TOP
    // =========================================================
    if (data.paginationTop && this.paginationTop) {
      this.paginationTop.innerHTML = data.paginationTop;
    }

    // =========================================================
    // 4. PAGINATION BOTTOM
    // =========================================================
    if (data.paginationBottom && this.paginationBottom) {
      this.paginationBottom.innerHTML = data.paginationBottom;
    }

    // =========================================================
    // 5. FALLBACK NO RESULTS
    // =========================================================
    if (!data.items && !data.results && this.resultDiv) {
      this.resultDiv.innerHTML = `<div class="${this.noResultsClass}">Aucun résultat</div>`;
    }
  }

  renderItems(items) {
    if (!this.resultDiv) return;

    this.resultDiv.innerHTML = "";

    items.forEach(item => {
      const el = document.createElement("div");
      el.className = this.itemClass;
      el.dataset.id = item.id;
      el.textContent = item.label;

      this.resultDiv.appendChild(el);
    });
  }
}
