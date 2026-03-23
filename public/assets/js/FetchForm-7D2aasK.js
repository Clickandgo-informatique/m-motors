// assets/js/FetchForm.js

console.log("FetchForm.js chargé");

/**
 * FetchForm
 * Module générique pour :
 * 1. Autocomplete sur un input
 * 2. Recherche + pagination AJAX sur un formulaire
 *
 * Utilisation :
 * - input avec `data-search-form` pour autocomplete
 * - container parent avec `data-fetch-form` pour recherche + pagination
 */

// assets/js/FetchForm.js

export default class FetchForm {
  constructor(input) {
    this.input = input;
    this.form = input.closest("form");
    if (!this.form) {
      console.warn("FetchForm : input hors formulaire", input);
      return;
    }

    this.endpoint = this.form.dataset.searchForm;
    if (!this.endpoint) {
      console.warn("FetchForm : dataset manquant → data-search-form");
    }

    this.queryParam = this.form.dataset.queryParam || this.input.name || "q";
    this.resultDivSelector = input.dataset.resultDiv
      ? input.dataset.resultDiv.startsWith("#")
        ? input.dataset.resultDiv
        : `#${input.dataset.resultDiv}`
      : "#results";

    this.resultDiv = document.querySelector(this.resultDivSelector);
    if (!this.resultDiv) {
      console.warn(
        "FetchForm : conteneur résultats introuvable",
        this.resultDivSelector
      );
      return;
    }

    this.page = 1;
    this.loadingMore = false;
    this.debounceTimer = null;

    this.injectSpinner();
    this.bindEvents();
  }

  injectSpinner() {
    this.spinner = document.createElement("div");
    this.spinner.classList.add("dropdown-spinner");
    this.spinner.innerHTML = `<div class="spinner-circle"></div>`;
  }

  showSpinner() {
    if (!this.spinner.parentNode) this.resultDiv.appendChild(this.spinner);
  }
  hideSpinner() {
    if (this.spinner.parentNode) this.spinner.remove();
  }

  bindEvents() {
    this.input.addEventListener("input", () => {
      const q = this.input.value.trim();
      if (!q) {
        this.clearResults();
        return;
      }
      this.debounce(() => this.search(q), 250);
    });

    document.addEventListener("click", e => {
      if (!this.form.contains(e.target)) this.clearResults();
    });

    this.resultDiv.addEventListener("scroll", () => {
      if (
        this.resultDiv.scrollTop + this.resultDiv.clientHeight >=
        this.resultDiv.scrollHeight - 20
      ) {
        this.loadMore();
      }
    });
  }

  debounce(callback, delay) {
    clearTimeout(this.debounceTimer);
    this.debounceTimer = setTimeout(callback, delay);
  }

  async search(q) {
    this.page = 1;
    const url = `${this.endpoint}?${encodeURIComponent(
      this.queryParam
    )}=${encodeURIComponent(q)}`;
    this.showSpinner();
    try {
      const response = await fetch(url);
      const payload = await response.json();
      const items = Array.isArray(payload)
        ? payload
        : payload.results || payload.items || payload.data || [];
      this.renderResults(items);
    } catch (e) {
      console.error("FetchForm error", e);
    }
    this.hideSpinner();
  }

  renderResults(items) {
    this.resultDiv.innerHTML = "";
    if (!items.length) {
      const div = document.createElement("div");
      div.className = "dropdown-no-results";
      div.textContent = "Aucun résultat";
      this.resultDiv.appendChild(div);
      return;
    }
    items.forEach(item => {
      const node = document.createElement("div");
      node.classList.add("dropdown-item");
      node.textContent =
        item.label || item.name || Object.values(item).join(" ");
      this.resultDiv.appendChild(node);
    });
    this.resultDiv.classList.add("active");
  }

  clearResults() {
    this.resultDiv.innerHTML = "";
    this.resultDiv.classList.remove("active");
  }

  async loadMore() {
    if (this.loadingMore) return;
    this.loadingMore = true;
    this.page++;
    const url = `${this.endpoint}?${encodeURIComponent(
      this.queryParam
    )}=${encodeURIComponent(this.input.value)}&page=${this.page}`;
    try {
      const response = await fetch(url);
      const payload = await response.json();
      const items = Array.isArray(payload)
        ? payload
        : payload.results || payload.items || payload.data || [];
      items.forEach(item => {
        const node = document.createElement("div");
        node.classList.add("dropdown-item");
        node.textContent =
          item.label || item.name || Object.values(item).join(" ");
        this.resultDiv.appendChild(node);
      });
    } catch (e) {
      console.error("FetchForm loadMore error", e);
    }
    this.loadingMore = false;
  }
}
