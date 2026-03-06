console.log("FetchForm.js chargé");

/**
 * FetchForm
 *
 * Module générique d'autocomplete / recherche AJAX
 * réutilisable dans n'importe quel projet.
 *
 * Tout est configuré via dataset HTML.
 */

export default class FetchForm {
  constructor(input) {

    /* ELEMENTS */

    this.input = input;
    this.form = input.closest("form");

    if (!this.form) {
      console.warn("FetchForm : input hors formulaire");
      return;
    }

    /* CONFIGURATION */

    this.endpoint = this.form.dataset.searchForm;
    this.queryParam = this.form.dataset.queryParam || "q";

    this.dropdownClass = this.form.dataset.dropdownClass || "dropdown-results";
    this.itemClass = this.form.dataset.itemClass || "dropdown-item";
    this.linkClass = this.form.dataset.linkClass || "dropdown-link";
    this.noResultsClass = this.form.dataset.noResultsClass || "dropdown-no-results";

    this.itemUrlPattern = this.form.dataset.itemUrl || null;

    /* RESULT DIV */

    this.resultDivSelector = this.input.dataset.resultDiv;
    this.resultDiv = document.querySelector(this.resultDivSelector);

    /* OPTIONS */

    this.autocomplete = this.input.dataset.autocomplete === "true";

    this.hiddenSelector = this.input.dataset.hiddenTarget || null;
    this.hiddenInput = this.hiddenSelector
      ? document.querySelector(this.hiddenSelector)
      : null;

    /* ETAT */

    this.page = 1;
    this.loadingMore = false;
    this.debounceTimer = null;

    this.activeIndex = -1;

    if (!this.endpoint || !this.resultDiv) {
      console.warn("FetchForm configuration invalide");
      return;
    }

    this.injectSpinner();
    this.bindEvents();
  }

  /* ========================= */
  /* SPINNER                   */
  /* ========================= */

  injectSpinner() {
    this.spinner = document.createElement("div");
    this.spinner.classList.add("dropdown-spinner");
    this.spinner.innerHTML = `<div class="spinner-circle"></div>`;
  }

  showSpinner() {
    this.resultDiv.appendChild(this.spinner);
  }

  hideSpinner() {
    this.spinner.remove();
  }

  /* ========================= */
  /* EVENTS                    */
  /* ========================= */

  bindEvents() {

    this.input.addEventListener("input", () => {

      const q = this.input.value.trim();

      if (!q) {
        this.clearResults();
        return;
      }

      this.debounce(() => this.search(q), 250);
    });

    document.addEventListener("click", (e) => {
      if (!this.form.contains(e.target)) {
        this.clearResults();
      }
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

  /* ========================= */
  /* DEBOUNCE                  */
  /* ========================= */

  debounce(callback, delay) {

    clearTimeout(this.debounceTimer);

    this.debounceTimer = setTimeout(callback, delay);

  }

  /* ========================= */
  /* SEARCH AJAX               */
  /* ========================= */

  async search(q) {

    this.page = 1;

    const url = `${this.endpoint}?${this.queryParam}=${encodeURIComponent(q)}`;

    this.showSpinner();

    try {

      const response = await fetch(url);

      const data = await response.json();

      this.renderResults(data);

    } catch (e) {

      console.error("FetchForm error", e);

    }

    this.hideSpinner();
  }

  /* ========================= */
  /* RENDER RESULTS            */
  /* ========================= */

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

      const wrapper = document.createElement("div");
      wrapper.classList.add(this.itemClass);

      wrapper.dataset.id = item.id;

      const link = document.createElement("a");

      link.classList.add(this.linkClass);
      link.href = "#";

      const label =
        item.label ||
        item.name ||
        Object.values(item).filter(v => typeof v === "string").join(" ");

      link.textContent = label;

      wrapper.appendChild(link);

      wrapper.addEventListener("click", () => this.selectItem(wrapper, label));

      this.resultDiv.appendChild(wrapper);
    });

    this.resultDiv.classList.add("active");
  }

  /* ========================= */
  /* SELECT ITEM               */
  /* ========================= */

  selectItem(item, label) {

    const id = item.dataset.id;

    if (this.autocomplete) {

      this.input.value = label;

      if (this.hiddenInput) {
        this.hiddenInput.value = id;
      }

      this.clearResults();
      return;
    }

    if (this.itemUrlPattern) {

      const url = this.itemUrlPattern.replace("__ID__", id);

      window.location.href = url;
    }

    this.clearResults();
  }

  /* ========================= */
  /* CLEAR                     */
  /* ========================= */

  clearResults() {

    this.resultDiv.innerHTML = "";
    this.resultDiv.classList.remove("active");

  }

  /* ========================= */
  /* LOAD MORE                 */
  /* ========================= */

  async loadMore() {

    if (this.loadingMore) return;

    this.loadingMore = true;
    this.page++;

    const url = `${this.endpoint}?${this.queryParam}=${encodeURIComponent(
      this.input.value
    )}&page=${this.page}`;

    try {

      const response = await fetch(url);

      const data = await response.json();

      data.forEach(item => {

        const wrapper = document.createElement("div");
        wrapper.classList.add(this.itemClass);

        wrapper.dataset.id = item.id;

        const link = document.createElement("a");

        link.classList.add(this.linkClass);
        link.href = "#";

        const label =
          item.label ||
          item.name ||
          Object.values(item).filter(v => typeof v === "string").join(" ");

        link.textContent = label;

        wrapper.appendChild(link);

        wrapper.addEventListener("click", () =>
          this.selectItem(wrapper, label)
        );

        this.resultDiv.appendChild(wrapper);

      });

    } catch (e) {

      console.error("loadMore error", e);

    }

    this.loadingMore = false;
  }
}