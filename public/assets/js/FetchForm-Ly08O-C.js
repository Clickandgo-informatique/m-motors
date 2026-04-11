// assets/js/FetchForm.js

/**
 * FetchForm
 *
 * Module générique pour :
 * 1. Autocomplete sur un input
 * 2. Recherche + pagination AJAX sur un formulaire
 * 3. Résultats cliquables avec URL ou modal
 *
 * Configuration via datasets sur le form/input :
 * - data-search-form : URL de l'endpoint AJAX
 * - data-result-div : id du container pour les résultats
 * - data-item-clickable : 'true' pour activer le clic sur les items
 * - data-item-url : clé de l'objet item contenant l'URL
 * - data-item-target : _self, _blank, modal
 * - data-pagination : 'ajax' pour activer la pagination
 * - data-pagination-mode : 'load-more' ou 'infinite-scroll'
 * - data-pagination-limit : nombre d'items par page
 */

export default class FetchForm {
  constructor(input) {
    this.input = input;
    this.form = input.closest("form");

    if (!this.form) {
      console.warn("FetchForm : input hors formulaire", input);
      return;
    }

    // Endpoint AJAX
    this.endpoint = this.form.dataset.searchForm;
    if (!this.endpoint) {
      console.error("FetchForm : dataset manquant → data-search-form");
    }

    // Paramètre de recherche (query param)
    this.queryParam = this.form.dataset.queryParam || this.input.name || "q";

    // Container des résultats
    this.resultDivSelector = input.dataset.resultDiv
      ? input.dataset.resultDiv.startsWith("#")
        ? input.dataset.resultDiv
        : `#${input.dataset.resultDiv}`
      : "#results";
    this.resultDiv = document.querySelector(this.resultDivSelector);
    if (!this.resultDiv) {
      console.error(
        "FetchForm : conteneur résultats introuvable",
        this.resultDivSelector
      );
    }

    // Pagination
    this.page = 1;
    this.loadingMore = false;

    // Débounce
    this.debounceTimer = null;

    // Vérification des datasets importants
    this.checkDatasets();

    // Spinner
    this.injectSpinner();

    // Événements
    this.bindEvents();
  }

  /**
   * Vérifie que les datasets essentiels sont définis
   */
  checkDatasets() {
    const requiredDatasets = [
      "searchForm",
      "resultDiv",
      "itemClickable",
      "itemUrl",
      "itemTarget"
    ];

    requiredDatasets.forEach(ds => {
      if (!(ds in this.form.dataset)) {
        console.warn(
          `FetchForm : dataset '${ds}' non défini sur le formulaire`
        );
      }
    });
  }

  /**
   * Injecte le spinner dans le container des résultats
   */
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
    // Saisie dans l'input
    this.input.addEventListener("input", () => {
      const q = this.input.value.trim();
      if (!q) {
        this.clearResults();
        return;
      }
      this.debounce(() => this.search(q), 250);
    });

    // Clic hors formulaire pour fermer le dropdown
    document.addEventListener("click", e => {
      if (!this.form.contains(e.target)) this.clearResults();
    });

    // Scroll pour pagination
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

  debounce(callback, delay) {
    clearTimeout(this.debounceTimer);
    this.debounceTimer = setTimeout(callback, delay);
  }

  /**
   * Recherche principale (page 1)
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

      const items = Array.isArray(payload)
        ? payload
        : payload.results || payload.items || payload.data || [];

      this.renderResults(items);
    } catch (e) {
      console.error("FetchForm search error", e);
    }
    this.hideSpinner();
  }

  /**
   * Pagination (load more)
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
      const items = Array.isArray(payload)
        ? payload
        : payload.results || payload.items || payload.data || [];

      this.renderResults(items, true); // append = true
    } catch (e) {
      console.error("FetchForm loadMore error", e);
    }
    this.loadingMore = false;
  }

  /**
   * Rendu des résultats
   * @param {Array} items
   * @param {Boolean} append - si true, ajoute au lieu de remplacer
   */
  renderResults(items, append = false) {
    if (!append) this.resultDiv.innerHTML = "";

    if (!items.length) {
      const div = document.createElement("div");
      div.className = "dropdown-no-results";
      div.textContent = "Aucun résultat";
      this.resultDiv.appendChild(div);
      return;
    }

    const urlKey = this.input.dataset.itemUrl || "url";
    const clickable = this.input.dataset.itemClickable === "true";
    const target = this.input.dataset.itemTarget || "_self";

    items.forEach(item => {
      const node = document.createElement("div");
      node.classList.add("dropdown-item");
      node.textContent =
        item.label || item.name || Object.values(item).join(" ");

      // Gestion du clic
      if (clickable && item[urlKey]) {
        node.style.cursor = "pointer";
        node.addEventListener("click", () => {
          if (target === "modal") {
            if (typeof openAjaxModal === "function") {
              openAjaxModal(item[urlKey]);
            } else {
              console.warn("FetchForm : fonction openAjaxModal non définie");
            }
          } else {
            window.open(item[urlKey], target);
          }
        });
      }

      this.resultDiv.appendChild(node);
    });

    this.resultDiv.classList.add("active");
  }

  /**
   * Vide les résultats
   */
  clearResults() {
    this.resultDiv.innerHTML = "";
    this.resultDiv.classList.remove("active");
  }
}
