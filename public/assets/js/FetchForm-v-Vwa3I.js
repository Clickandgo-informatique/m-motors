// assets/js/FetchForm.js
export default class FetchForm {
  /**
   * Crée une instance FetchForm pour gérer un formulaire avec fetch/ajax
   * @param {HTMLFormElement} form
   */
  constructor(form) {
    console.log("FetchForm.js démarré");
    if (!(form instanceof HTMLFormElement)) {
      throw new Error("[FetchForm] Element fourni n'est pas un formulaire.");
    }
    this.form = form;

    // Dataset options
    this.resultDivId = form.dataset.resultDiv || "results";
    this.resultLinks = form.dataset.resultLinks === "true";
    this.itemClass = form.dataset.itemClass || "item";
    this.linkClass = form.dataset.linkClass || "item-link";
    this.noResultsClass = form.dataset.noResultsClass || "no-results";
    this.itemUrlTemplate = form.dataset.itemUrl || "";
    this.ajaxModal = form.dataset.ajaxModal === "true";
    this.autocomplete = form.dataset.autocomplete === "true";
    this.paginationMode = form.dataset.paginationMode || "load-more";
    this.paginationLimit = parseInt(form.dataset.paginationLimit) || 10;

    // Element container pour afficher les résultats
    this.resultsContainer = document.getElementById(this.resultDivId);
    if (!this.resultsContainer) {
      console.warn(`[FetchForm] Container #${this.resultDivId} introuvable`);
    }

    // Debounce timer
    this.debounceTimer = null;
    this.debounceDelay = 300;

    // Initialisation des événements
    this.initEvents();
  }

  /**
   * Initialise les événements du formulaire et de l'input
   */
  initEvents() {
    // Soumission du formulaire (si recherche classique)
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.fetchResults();
    });

    // Input pour autocomplete
    if (this.autocomplete) {
      const input = this.form.querySelector(
        'input[name="' + (this.form.dataset.name || "q") + '"]'
      );
      if (input) {
        input.addEventListener("input", e => {
          clearTimeout(this.debounceTimer);
          this.debounceTimer = setTimeout(() => {
            this.fetchResults(input.value);
          }, this.debounceDelay);
        });
      }
    }

    // Bouton toggle/search
    const toggleBtn = this.form.querySelector("[data-search-toggle]");
    if (toggleBtn) {
      toggleBtn.addEventListener("click", e => {
        e.preventDefault();
        this.fetchResults();
      });
    }
  }

  /**
   * Récupère les résultats depuis le serveur via fetch
   * @param {string} [query]
   */
  async fetchResults(query = null) {
    if (!this.resultsContainer) return;

    const url = this.form.action;
    const formData = new FormData(this.form);
    if (query !== null) {
      formData.set(this.form.dataset.name || "q", query);
    }

    // Préparation de l'URL ou POST
    const fetchOptions = {
      method: this.form.method.toUpperCase() || "POST",
      headers: { "X-Requested-With": "XMLHttpRequest" }
    };
    if (fetchOptions.method === "POST") {
      fetchOptions.body = formData;
    } else {
      const params = new URLSearchParams(formData).toString();
      if (params) fetchOptions.url = url + "?" + params;
    }

    try {
      const response = await fetch(url, fetchOptions);
      if (!response.ok) throw new Error(`Erreur HTTP ${response.status}`);
      const data = await response.json();

      if (!data || typeof data.results !== "string") {
        throw new Error('La réponse serveur ne contient pas de "results"');
      }

      this.renderResults(data.results);
    } catch (err) {
      console.error("[FetchForm]", err);
    }
  }

  /**
   * Affiche les résultats dans le container
   * @param {string|Array} results
   */
  renderResults(results) {
    if (!this.resultsContainer) return;

    // Nettoyage
    this.resultsContainer.innerHTML = "";

    if (!results || results.length === 0) {
      this.resultsContainer.innerHTML = `<div class="${this.noResultsClass}">Aucun résultat</div>`;
      return;
    }

    if (this.resultLinks) {
      // Génère des liens <a> à partir de l'HTML fourni ou de dataset
      let fragment = document.createDocumentFragment();
      // Si results est déjà du HTML string
      if (typeof results === "string") {
        this.resultsContainer.innerHTML = results;
        return;
      }

      results.forEach(item => {
        const div = document.createElement("div");
        div.classList.add(this.itemClass);
        if (item.id && this.itemUrlTemplate) {
          const link = document.createElement("a");
          link.href = this.itemUrlTemplate.replace("ID_PLACEHOLDER", item.id);
          link.classList.add(this.linkClass);
          link.innerHTML = item.label || item.name || "";
          if (this.ajaxModal) link.dataset.ajaxModal = "true";
          div.appendChild(link);
        } else {
          div.textContent = item.label || item.name || "";
        }
        fragment.appendChild(div);
      });
      this.resultsContainer.appendChild(fragment);
    } else {
      // Injecte directement le HTML
      this.resultsContainer.innerHTML = results;
    }
  }
}

// --- FIN FetchForm.js ---
