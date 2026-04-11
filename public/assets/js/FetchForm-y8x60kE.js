// assets/js/FetchForm.js

console.log("FetchForm.js chargé");

/**
 * FetchForm
 *
 * Module générique pour :
 * - Autocomplete (résultats JSON tableau)
 * - Recherche AJAX (résultats HTML Symfony)
 * - Pagination infinie (scroll)
 *
 * Fonctionne automatiquement selon le type de réponse du backend :
 * - string HTML → injection directe
 * - array JSON → rendu en liste
 */

export default class FetchForm {
  constructor(input) {
    this.input = input;
    this.form = input.closest("form");

    if (!this.form) {
      console.warn("FetchForm : input hors formulaire", input);
      return;
    }

    // URL cible (obligatoire)
    this.endpoint = this.form.dataset.searchForm;
    if (!this.endpoint) {
      console.warn("FetchForm : data-search-form manquant");
    }

    // Nom du paramètre de recherche (q par défaut)
    this.queryParam = this.form.dataset.queryParam || this.input.name || "q";

    // Sélecteur du container de résultats
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
    }

    // Pagination
    this.page = 1;
    this.loadingMore = false;

    // Debounce
    this.debounceTimer = null;

    this.injectSpinner();
    this.bindEvents();
  }

  /**
   * Ajoute un spinner de chargement
   */
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
    if (this.spinner.parentNode) {
      this.spinner.remove();
    }
  }

  /**
   * Bind des événements
   */
  bindEvents() {
    // Input utilisateur (autocomplete / search)
    this.input.addEventListener("input", () => {
      const q = this.input.value.trim();

      if (!q) {
        this.clearResults();
        return;
      }

      this.debounce(() => this.search(q), 250);
    });

    // Click extérieur → ferme dropdown
    document.addEventListener("click", e => {
      if (!this.form.contains(e.target)) {
        this.clearResults();
      }
    });

    // Scroll pour pagination infinie
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

  /**
   * Debounce pour limiter les requêtes
   */
  debounce(callback, delay) {
    clearTimeout(this.debounceTimer);
    this.debounceTimer = setTimeout(callback, delay);
  }

  /**
   * Requête principale
   */
  async search(q) {
    this.page = 1;

    const url = `${this.endpoint}?${encodeURIComponent(
      this.queryParam
    )}=${encodeURIComponent(q)}`;

    this.showSpinner();

    try {
      const response = await fetch(url);
      const payload = await response.json();

      console.log("FetchForm payload:", payload);

      /**
       * CAS 1 : réponse HTML (Symfony renderView)
       */
      if (typeof payload.results === "string") {
        this.resultDiv.innerHTML = payload.results;
        return;
      }

      /**
       * CAS 2 : réponse tableau (autocomplete JSON)
       */
      const items = Array.isArray(payload)
        ? payload
        : payload.items || payload.data || [];

      this.renderResults(items);
    } catch (e) {
      console.error("FetchForm error", e);
    }

    this.hideSpinner();
  }

  /**
   * Affichage des résultats autocomplete
   */
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

  /**
   * Nettoyage des résultats
   */
  clearResults() {
    if (!this.resultDiv) return;

    this.resultDiv.innerHTML = "";
    this.resultDiv.classList.remove("active");
  }

  /**
   * Chargement pagination (scroll infini)
   */
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

      /**
       * CAS 1 : HTML (pagination Symfony)
       */
      if (typeof payload.results === "string") {
        this.resultDiv.innerHTML += payload.results;
        return;
      }

      /**
       * CAS 2 : tableau JSON
       */
      const items = Array.isArray(payload)
        ? payload
        : payload.items || payload.data || [];

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
