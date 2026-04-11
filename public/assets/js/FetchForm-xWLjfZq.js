export default class FetchForm {
  constructor(input) {
    this.input = input;
    this.form = input.closest("form");
    if (!this.form) return;

    this.endpoint = this.form.dataset.searchForm;
    this.queryParam = this.form.dataset.queryParam || this.input.name || "q";
    this.resultDivSelector = input.dataset.resultDiv
      ? input.dataset.resultDiv.startsWith("#")
        ? input.dataset.resultDiv
        : `#${input.dataset.resultDiv}`
      : "#results";

    this.resultDiv = document.querySelector(this.resultDivSelector);
    if (!this.resultDiv) return;

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
    // Input autocomplete / recherche
    this.input.addEventListener("input", () => {
      const q = this.input.value.trim();
      if (!q) {
        this.clearResults();
        return;
      }
      this.debounce(() => this.search(q), 250);
    });

    // Click hors form → fermer dropdown
    document.addEventListener("click", e => {
      if (!this.form.contains(e.target)) this.clearResults();
    });

    // Scroll pour pagination
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
    const url = new URL(this.endpoint, window.location.origin);
    url.searchParams.set(this.queryParam, q);

    // Détecter autocomplete
    if (this.input.dataset.autocomplete) {
      url.searchParams.set("autocomplete", "1");
    }

    this.showSpinner();

    try {
      const response = await fetch(url);
      const payload = await response.json();

      // Si autocomplete → tableau items
      if (payload.items) {
        this.renderResults(payload.items);
      } else if (payload.results) {
        // Recherche complète → HTML
        this.resultDiv.innerHTML = payload.results;
      }
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

    const url = new URL(this.endpoint, window.location.origin);
    url.searchParams.set(this.queryParam, this.input.value);
    url.searchParams.set("page", this.page);
    if (this.input.dataset.autocomplete)
      url.searchParams.set("autocomplete", "1");

    try {
      const response = await fetch(url);
      const payload = await response.json();

      if (payload.items) {
        this.renderResults(payload.items);
      } else if (payload.results) {
        const div = document.createElement("div");
        div.innerHTML = payload.results;
        this.resultDiv.appendChild(div);
      }
    } catch (e) {
      console.error("FetchForm loadMore error", e);
    }

    this.loadingMore = false;
  }
}
