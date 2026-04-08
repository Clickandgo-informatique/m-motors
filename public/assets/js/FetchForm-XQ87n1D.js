// FetchForm.js
export class FetchForm {
  /**
   * Constructeur : initialise le formulaire et ses éléments
   * @param {HTMLFormElement} form
   */
  constructor(form) {
    this.form = form;

    // Dataset principaux
    this.resultDivId = form.dataset.resultDiv || "results";
    this.resultsContainer = document.getElementById(this.resultDivId);
    this.itemClass = form.dataset.itemClass || "vehicle-item";
    this.linkClass = form.dataset.linkClass || "vehicle-link";
    this.noResultsClass = form.dataset.noResultsClass || "dropdown-no-results";
    this.itemUrl = form.dataset.itemUrl || "";
    this.ajaxModal = form.dataset.ajaxModal === "true";
    this.resultLinks = form.dataset.resultLinks === "true";

    // Input
    this.input = form.querySelector(
      'input[name="' + (form.querySelector("input").name || "q") + '"]'
    );

    // Timer debounce
    this.debounceTimer = null;
    this.debounceDelay = 300; // ms

    // Vérification du container
    if (!this.resultsContainer) {
      console.error(`[FetchForm] Container #${this.resultDivId} introuvable`);
      return;
    }

    // Bind events
    this._bindEvents();
  }

  /**
   * Bind des événements
   */
  _bindEvents() {
    // Input typing avec debounce
    this.input.addEventListener("input", () => {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(
        () => this._fetchResults(),
        this.debounceDelay
      );
    });

    // Bouton toggle
    const toggleBtn = this.form.querySelector("[data-search-toggle]");
    if (toggleBtn) {
      toggleBtn.addEventListener("click", () => this._fetchResults());
    }

    // Submit standard (empêche rechargement page)
    this.form.addEventListener("submit", e => e.preventDefault());
  }

  /**
   * Récupération des résultats via fetch
   */
  async _fetchResults() {
    const query = this.input.value.trim();
    if (!query) {
      this.resultsContainer.innerHTML = "";
      return;
    }

    // Construire l'URL GET
    const url = `${this.form.action}?${encodeURIComponent(
      this.input.name
    )}=${encodeURIComponent(query)}`;

    try {
      const response = await fetch(url);
      if (!response.ok) throw new Error("Erreur fetch");

      // On attend un JSON {id, label, url?}[]
      const items = await response.json();
      this._buildResults(items);
    } catch (err) {
      console.error("[FetchForm] Fetch échoué:", err);
    }
  }

  /**
   * Construction dynamique des résultats
   * @param {Array} items
   */
  _buildResults(items) {
    this.resultsContainer.innerHTML = "";

    if (!items.length) {
      const div = document.createElement("div");
      div.className = this.noResultsClass;
      div.textContent = "Aucun résultat";
      this.resultsContainer.appendChild(div);
      return;
    }

    items.forEach(item => {
      let element;

      if (this.resultLinks) {
        element = document.createElement("a");
        element.href =
          item.url || this.itemUrl.replace("ID_PLACEHOLDER", item.id);
        element.className = `${this.itemClass} ${this.linkClass}`;

        if (this.ajaxModal) {
          element.addEventListener("click", e => {
            e.preventDefault();
            this._openModal(element.href);
          });
        }
      } else {
        element = document.createElement("div");
        element.className = this.itemClass;
      }

      element.textContent = item.label;
      element.dataset.id = item.id;

      this.resultsContainer.appendChild(element);
    });
  }

  /**
   * Logique pour ouvrir une modale
   * @param {string} url
   */
  _openModal(url) {
    // Implémentation custom selon ton modal system
    console.log("Open modal for:", url);
  }
}

/**
 * Initialisation automatique sur toutes les forms
 */
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".fetch-form").forEach(form => new FetchForm(form));
});
