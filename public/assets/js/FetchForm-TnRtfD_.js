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

export default class FetchForm {
  constructor(input) {
    /* =========================
     * ELEMENTS DE BASE
     * ========================= */
    this.input = input;
    this.form = input.closest("form");

    if (!this.form) {
      console.warn("FetchForm : input hors formulaire", input);
      return;
    }

    /* =========================
     * DETECTION MODE AJAX
     * ========================= */
    this.container = this.input.closest("[data-fetch-form]");
    this.isAjaxPage = !!this.container;

    /* =========================
     * CONFIG AUTOCOMPLETE
     * ========================= */
    this.endpoint = this.form.dataset.searchForm;
    this.queryParam = this.form.dataset.queryParam || this.input.name || "q";
    this.dropdownClass = this.form.dataset.dropdownClass || "dropdown-results";
    this.itemClass = this.form.dataset.itemClass || "dropdown-item";
    this.linkClass = this.form.dataset.linkClass || "dropdown-link";
    this.noResultsClass =
      this.form.dataset.noResultsClass || "dropdown-no-results";
    this.itemUrlPattern = this.form.dataset.itemUrl || null;
    this.ajaxModal = this.form.hasAttribute("data-ajax-modal");

    /* =========================
     * CONFIG AJAX PAGE
     * ========================= */
    if (this.isAjaxPage) {
      this.targets = {};

      // Récupération de tous les data-target
      this.container.querySelectorAll("[data-target]").forEach(el => {
        const key = el.dataset.target;
        if (!this.targets[key]) this.targets[key] = [];
        this.targets[key].push(el);
      });

      this.fetchUrl = this.container.dataset.fetchUrl;
      if (!this.fetchUrl) console.warn("FetchForm : data-fetch-url manquant");
    }

    /* =========================
     * RESULT DIV (AUTOCOMPLETE UNIQUEMENT)
     * ========================= */
    if (!this.isAjaxPage) {
      const resultDivAttr = this.input.dataset.resultDiv;
      if (!resultDivAttr) {
        console.warn("FetchForm : dataset manquant → data-result-div");
        return;
      }

      this.resultDivSelector = resultDivAttr.startsWith("#")
        ? resultDivAttr
        : `#${resultDivAttr}`;
      this.resultDiv = document.querySelector(this.resultDivSelector);
      if (!this.resultDiv) {
        console.warn(
          "FetchForm : conteneur résultats introuvable",
          this.resultDivSelector
        );
        return;
      }
    }

    /* =========================
     * STATE
     * ========================= */
    this.page = 1;
    this.loadingMore = false;
    this.debounceTimer = null;

    this.injectSpinner();
    this.bindEvents();
  }

  /* =========================
   * SPINNER
   * ========================= */
  injectSpinner() {
    this.spinner = document.createElement("div");
    this.spinner.classList.add("dropdown-spinner");
    this.spinner.innerHTML = `<div class="spinner-circle"></div>`;
  }

  showSpinner() {
    if (!this.spinner.parentNode && this.resultDiv) {
      this.resultDiv.appendChild(this.spinner);
    }
  }

  hideSpinner() {
    if (this.spinner.parentNode) this.spinner.remove();
  }

  /* =========================
   * EVENTS
   * ========================= */
  bindEvents() {
    /* -------- SUBMIT AJAX -------- */
    if (this.isAjaxPage) {
      this.form.addEventListener("submit", e => {
        e.preventDefault();
        this.fetchPage(1);
      });

      /* -------- CLICK PAGINATION -------- */
      this.container.addEventListener("click", e => {
        const link = e.target.closest("[data-page]");
        if (!link) return;
        e.preventDefault();
        const page = parseInt(link.dataset.page, 10);
        if (!isNaN(page)) this.fetchPage(page);
      });
    }

    /* -------- AUTOCOMPLETE INPUT -------- */
    this.input.addEventListener("input", () => {
      if (!this.endpoint) return;
      const q = this.input.value.trim();
      if (!q) {
        this.clearResults();
        return;
      }
      this.debounce(() => this.search(q), 250);
    });

    /* -------- CLICK EXTERIEUR -------- */
    document.addEventListener("click", e => {
      if (!this.form.contains(e.target)) this.clearResults();
    });

    /* -------- SCROLL INFINI -------- */
    if (this.resultDiv) {
      this.resultDiv.addEventListener("scroll", () => {
        if (
          this.resultDiv.scrollTop + this.resultDiv.clientHeight >=
          this.resultDiv.scrollHeight - 20
        ) {
          this.loadMore();
        }
      });
    }
  }

  /* =========================
   * DEBOUNCE
   * ========================= */
  debounce(callback, delay) {
    clearTimeout(this.debounceTimer);
    this.debounceTimer = setTimeout(callback, delay);
  }

  /* =========================
   * AJAX PAGE (RESULTATS + PAGINATION)
   * ========================= */
  async fetchPage(page = 1) {
    if (!this.fetchUrl) return;

    const formData = new FormData(this.form);
    const data = { q: formData.get("q") || null, filters: {} };

    try {
      const response = await fetch(`${this.fetchUrl}?page=${page}`, {
        method: "POST",
        body: JSON.stringify(data)
      });

      const json = await response.json();
      this.updateTargets(json);
      window.scrollTo({ top: 0, behavior: "smooth" });
    } catch (e) {
      console.error("FetchForm fetchPage error", e);
    }
  }

  updateTargets(data) {
    Object.entries(data).forEach(([key, html]) => {
      const targets = this.targets[key];
      if (!targets) return;
      targets.forEach(el => (el.innerHTML = html));
    });
  }

  /* =========================
   * AUTOCOMPLETE
   * ========================= */
  async search(q) {
    this.page = 1;
    const url = `${this.endpoint}?${encodeURIComponent(
      this.queryParam
    )}=${encodeURIComponent(q)}`;

    this.showSpinner();

    try {
      const response = await fetch(url);
      const payload = await response.json();
      const items = this.normalizePayload(payload);
      this.renderResults(items);
    } catch (e) {
      console.error("FetchForm error", e);
    }

    this.hideSpinner();
  }

  normalizePayload(payload) {
    if (!payload) return [];
    if (Array.isArray(payload)) return payload;
    if (payload.results) return payload.results;
    if (payload.items) return payload.items;
    if (payload.data) return payload.data;
    return [];
  }

  renderResults(items) {
    if (!this.resultDiv) return;
    this.resultDiv.innerHTML = "";

    if (!items.length) {
      const div = document.createElement("div");
      div.className = this.noResultsClass;
      div.textContent = "Aucun résultat";
      this.resultDiv.appendChild(div);
      return;
    }

    items.forEach(item => {
      const node = this.createItem(item);
      if (node) this.resultDiv.appendChild(node);
    });

    this.resultDiv.classList.add("active");
  }

  createItem(item) {
    if (!item.id) return null;

    const wrapper = document.createElement("div");
    wrapper.classList.add(this.itemClass);
    wrapper.dataset.id = item.id;

    const link = document.createElement("a");
    link.classList.add(this.linkClass);

    const label =
      item.label ||
      item.name ||
      Object.values(item)
        .filter(v => typeof v === "string")
        .join(" ");

    link.textContent = label;

    if (this.itemUrlPattern) {
      link.href = this.itemUrlPattern.replace(/__ID__/g, item.id);
      if (this.ajaxModal) link.dataset.ajaxModal = "";
    } else {
      link.href = "#";
    }

    wrapper.appendChild(link);
    return wrapper;
  }

  clearResults() {
    if (!this.resultDiv) return;
    this.resultDiv.innerHTML = "";
    this.resultDiv.classList.remove("active");
  }

  async loadMore() {
    if (this.loadingMore || !this.endpoint) return;

    this.loadingMore = true;
    this.page++;

    const url = `${this.endpoint}?${encodeURIComponent(
      this.queryParam
    )}=${encodeURIComponent(this.input.value)}&page=${this.page}`;

    try {
      const response = await fetch(url);
      const payload = await response.json();
      const items = this.normalizePayload(payload);
      items.forEach(item => {
        const node = this.createItem(item);
        if (node) this.resultDiv.appendChild(node);
      });
    } catch (e) {
      console.error("FetchForm loadMore error", e);
    }

    this.loadingMore = false;
  }
}
