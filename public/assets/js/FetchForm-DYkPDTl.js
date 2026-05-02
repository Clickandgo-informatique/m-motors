/**
 * FetchForm
 * Gestion AJAX : recherche + filtres + pagination + view switch
 * Version stabilisée : anti double instance + contexte sécurisé
 */

export default class FetchForm {
  constructor(input) {
    if (!input) return;

    /**
     * Protection anti double instanciation sur le même input
     * évite les doublons lors des re-renders AJAX
     */
    if (input.dataset.fetchFormInit === "1") {
      return;
    }

    input.dataset.fetchFormInit = "1";

    this.input = input;
    this.form = input.form;

    this.url = this.form?.action || "";

    this.gridContainer = document.getElementById("vehicles-search-results");
    this.paginationTop = document.querySelector(
      '[data-target="pagination-top"]'
    );
    this.paginationBottom = document.querySelector(
      '[data-target="pagination-bottom"]'
    );

    this.requestId = 0;
    this.controller = null;
    this.timeout = null;

    this.currentPage = 1;

    this.init();
  }

  init() {
    this.input.addEventListener("input", e => this.onInput(e));

    this.bindFilters();
    this.bindViewSwitch();
    this.bindPagination();
  }

  /**
   * Valeur de recherche courante
   */
  getSearchValue() {
    return this.input?.value?.trim() || "";
  }

  /**
   * Vue active (grid / table)
   */
  getViewValue() {
    const checked = document.querySelector('input[name="view"]:checked');
    return checked ? checked.value : "grid";
  }

  /**
   * Gestion input search avec debounce
   */
  onInput(e) {
    clearTimeout(this.timeout);

    const value = (e.target.value || "").trim();

    this.timeout = setTimeout(() => {
      this.currentPage = 1;
      this.fetchResults(value);
    }, 300);
  }

  /**
   * Gestion des filtres classiques
   */
  bindFilters() {
    if (!this.form) return;

    this.form.querySelectorAll("input, select").forEach(el => {
      if (el === this.input) return;

      el.addEventListener("change", () => {
        this.currentPage = 1;
        this.fetchResults(this.getSearchValue());
      });
    });
  }

  /**
   * Switch grid/table
   */
  bindViewSwitch() {
    console.log('bindToggleTableGrid appelé');
    const switchForm = document.getElementById("view-switch-form");

    if (!switchForm) return;

    switchForm.addEventListener("change", () => {
      this.currentPage = 1;
      this.fetchResults(this.getSearchValue());
    });
  }

  /**
   * Pagination AJAX
   * IMPORTANT : utilisation de dataset.page (plus fiable que href)
   */
  bindPagination() {
    const self = this;

    document.addEventListener("click", function(e) {
      const link = e.target.closest(".pagination a");
      if (!link) return;

      e.preventDefault();

      const page = parseInt(link.dataset.page || "1");

      self.currentPage = page;

      self.fetchResults(self.getSearchValue());
    });
  }

  /**
   * Requête AJAX principale
   */
  async fetchResults(query) {
    if (!this.url) return;

    const currentRequest = ++this.requestId;

    try {
      if (this.controller) {
        this.controller.abort();
      }

      this.controller = new AbortController();

      const formData = new FormData(this.form);

      formData.set("q", query);
      formData.set("page", this.currentPage);
      formData.set("filters[view]", this.getViewValue());

      const params = new URLSearchParams(formData);

      const response = await fetch(`${this.url}?${params.toString()}`, {
        signal: this.controller.signal,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          Accept: "application/json"
        }
      });

      const data = await response.json();

      if (currentRequest !== this.requestId) return;

      this.render(data);
    } catch (err) {
      if (err.name !== "AbortError") {
        console.error("[FetchForm]", err);
      }
    }
  }

  /**
   * Injection DOM des résultats
   */
  render(data) {
    if (!data) return;

    if (this.gridContainer && data.results !== undefined) {
      this.gridContainer.innerHTML = data.results;
    }

    if (this.paginationTop && data.paginationTop !== undefined) {
      this.paginationTop.innerHTML = data.paginationTop;
    }

    if (this.paginationBottom && data.paginationBottom !== undefined) {
      this.paginationBottom.innerHTML = data.paginationBottom;
    }
  }
}
