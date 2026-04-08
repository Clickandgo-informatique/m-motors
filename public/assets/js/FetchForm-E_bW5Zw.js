// assets/js/FetchForm.js
// Module FetchForm : autocomplete + recherche/pagination AJAX
// Version complète, gère liens, modales, et contrôle des datasets

export default class FetchForm {
  constructor(input) {
    this.input = input;
    this.form = input.closest("form");

    if (!this.form) {
      console.warn("FetchForm : input hors formulaire", input);
      return;
    }

    // URL endpoint pour la recherche
    this.endpoint = this.form.dataset.searchForm;
    if (!this.endpoint)
      console.warn("FetchForm : dataset manquant → data-search-form");

    // Paramètre de query pour la recherche
    this.queryParam = this.form.dataset.queryParam || this.input.name || "q";

    // Conteneur pour les résultats
    this.resultDivSelector = input.dataset.resultDiv
      ? input.dataset.resultDiv.startsWith("#")
        ? input.dataset.resultDiv
        : `#${input.dataset.resultDiv}`
      : "#results";

    this.resultDiv = document.querySelector(this.resultDivSelector);
    if (!this.resultDiv)
      console.warn(
        "FetchForm : conteneur résultats introuvable",
        this.resultDivSelector
      );

    // Classe CSS pour chaque item
    this.itemClass = input.dataset.itemClass || "dropdown-item";
    // Classe CSS pour les liens si besoin
    this.linkClass = input.dataset.linkClass || "dropdown-link";
    // Classe CSS pour "no results"
    this.noResultsClass = input.dataset.noResultsClass || "dropdown-no-results";
    // Nom de la propriété contenant l'URL ou ID
    this.urlField = input.dataset.urlField || "url";
    // URL ou modale par défaut si click sur item
    this.itemUrl = input.dataset.itemUrl || null;
    this.ajaxModal = input.dataset.ajaxModal !== undefined;

    this.page = 1;
    this.loadingMore = false;
    this.debounceTimer = null;

    // Vérification des datasets manquants
    this.checkDatasets();

    this.injectSpinner();
    this.bindEvents();
  }

  // Vérifie les datasets essentiels
  checkDatasets() {
    if (!this.endpoint) console.warn("FetchForm : data-search-form manquant");
    if (!this.resultDiv)
      console.warn("FetchForm : container results introuvable");
    if (!this.queryParam) console.warn("FetchForm : queryParam non défini");
  }

  // Spinner lors du chargement
  injectSpinner() {
    this.spinner = document.createElement("div");
    this.spinner.classList.add("dropdown-spinner");
    this.spinner.innerHTML = `<div class="spinner-circle"></div>`;
  }

  showSpinner() {
    if (!this.spinner.parentNode && this.resultDiv)
      this.resultDiv.appendChild(this.spinner);
  }

  hideSpinner() {
    if (this.spinner.parentNode) this.spinner.remove();
  }

  // Événements : input, scroll, click extérieur
  bindEvents() {
    // Recherche au typing
    this.input.addEventListener("input", () => {
      const q = this.input.value.trim();
      if (!q) {
        this.clearResults();
        return;
      }
      this.debounce(() => this.search(q), 250);
    });

    // Clique en dehors du formulaire => cache les résultats
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

  // Debounce pour limiter le nombre de requêtes
  debounce(callback, delay) {
    clearTimeout(this.debounceTimer);
    this.debounceTimer = setTimeout(callback, delay);
  }

  // Recherche principale
  async search(q) {
    this.page = 1;
    const url = `${this.endpoint}?${encodeURIComponent(
      this.queryParam
    )}=${encodeURIComponent(q)}`;
    this.showSpinner();

    try {
      const response = await fetch(url);
      const payload = await response.json();

      // --- Cas HTML brut ---
      if (payload.results && typeof payload.results === "string") {
        this.resultDiv.innerHTML = payload.results;
        return;
      }

      // --- Cas JSON tableau d'items ---
      const items = Array.isArray(payload)
        ? payload
        : payload.results || payload.items || payload.data || [];

      if (!Array.isArray(items)) {
        console.error("FetchForm : items n'est pas un tableau", items);
        this.resultDiv.innerHTML = `<div class="${this.noResultsClass}">Aucun résultat</div>`;
        return;
      }

      this.renderResults(items);
    } catch (e) {
      console.error("FetchForm search error", e);
    }

    this.hideSpinner();
  }

  // Affichage des résultats
  renderResults(items) {
    this.resultDiv.innerHTML = "";

    if (!items.length) {
      const div = document.createElement("div");
      div.className = this.noResultsClass;
      div.textContent = "Aucun résultat";
      this.resultDiv.appendChild(div);
      return;
    }

    items.forEach(item => {
      const node = document.createElement("div");
      node.classList.add(this.itemClass);

      // --- Si itemUrl ou dataset indique lien ou modal ---
      let url = this.itemUrl || item[this.urlField] || "#";
      if (this.ajaxModal) {
        node.dataset.ajaxModal = true;
      }

      if (url && url !== "#") {
        const link = document.createElement("a");
        link.href = url;
        link.classList.add(this.linkClass);
        link.textContent =
          item.label || item.name || Object.values(item).join(" ");
        node.appendChild(link);
      } else {
        node.textContent =
          item.label || item.name || Object.values(item).join(" ");
      }

      this.resultDiv.appendChild(node);
    });

    this.resultDiv.classList.add("active");
  }

  // Clear results
  clearResults() {
    if (this.resultDiv) {
      this.resultDiv.innerHTML = "";
      this.resultDiv.classList.remove("active");
    }
  }

  // Chargement de la page suivante
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

      // Si HTML brut
      if (payload.results && typeof payload.results === "string") {
        this.resultDiv.insertAdjacentHTML("beforeend", payload.results);
        this.loadingMore = false;
        return;
      }

      const items = Array.isArray(payload)
        ? payload
        : payload.results || payload.items || payload.data || [];

      if (!Array.isArray(items)) {
        console.error("FetchForm loadMore : items n'est pas un tableau", items);
        this.loadingMore = false;
        return;
      }

      items.forEach(item => {
        const node = document.createElement("div");
        node.classList.add(this.itemClass);

        let url = this.itemUrl || item[this.urlField] || "#";
        if (this.ajaxModal) node.dataset.ajaxModal = true;

        if (url && url !== "#") {
          const link = document.createElement("a");
          link.href = url;
          link.classList.add(this.linkClass);
          link.textContent =
            item.label || item.name || Object.values(item).join(" ");
          node.appendChild(link);
        } else {
          node.textContent =
            item.label || item.name || Object.values(item).join(" ");
        }

        this.resultDiv.appendChild(node);
      });
    } catch (e) {
      console.error("FetchForm loadMore error", e);
    }

    this.loadingMore = false;
  }
}
