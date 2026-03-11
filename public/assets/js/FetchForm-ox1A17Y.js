console.log("FetchForm.js chargé");

/**
 * FetchForm
 * Module générique d'autocomplete / recherche AJAX
 */

export default class FetchForm {
  constructor(input) {
    /* ELEMENTS */

    this.input = input;
    this.form = input.closest("form");

    if (!this.form) {
      console.warn("FetchForm : input hors formulaire", input);
      return;
    }

    /* CONFIG DATASETS */

    this.endpoint = this.form.dataset.searchForm;

    if (!this.endpoint) {
      console.warn("FetchForm : dataset manquant → data-search-form");
    }

    this.queryParam = this.form.dataset.queryParam || this.input.name || "q";

    this.dropdownClass = this.form.dataset.dropdownClass || "dropdown-results";

    this.itemClass = this.form.dataset.itemClass || "dropdown-item";

    this.linkClass = this.form.dataset.linkClass || "dropdown-link";

    this.noResultsClass =
      this.form.dataset.noResultsClass || "dropdown-no-results";

    this.itemUrlPattern = this.form.dataset.itemUrl || null;

    /* BOOLEAN ATTRIBUT */

    this.ajaxModal = this.form.hasAttribute("data-ajax-modal");

    /* RESULT DIV */

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

    /* ACTION */

    this.action = this.input.dataset.action || "links";

    if (!["links", "select"].includes(this.action)) {
      console.warn("FetchForm : data-action invalide →", this.action);
    }

    /* HIDDEN TARGET */

    this.hiddenSelector = this.input.dataset.hiddenTarget || null;

    this.hiddenInput = this.hiddenSelector
      ? document.querySelector(this.hiddenSelector)
      : null;

    /* STATE */

    this.page = 1;
    this.loadingMore = false;
    this.debounceTimer = null;

    this.injectSpinner();
    this.bindEvents();
  }

  /* NORMALISATION JSON */

  normalizePayload(payload) {
    if (!payload) return [];

    if (Array.isArray(payload)) return payload;

    if (payload.results) return payload.results;
    if (payload.items) return payload.items;
    if (payload.data) return payload.data;

    console.warn("FetchForm : JSON inattendu", payload);

    return [];
  }

  /* SPINNER */

  injectSpinner() {
    this.spinner = document.createElement("div");
    this.spinner.classList.add("dropdown-spinner");

    this.spinner.innerHTML = `<div class="spinner-circle"></div>`;
  }

  showSpinner() {
    if (!this.spinner.parentNode) {
      this.resultDiv.appendChild(this.spinner);
    }
  }

  hideSpinner() {
    if (this.spinner.parentNode) {
      this.spinner.remove();
    }
  }

  /* EVENTS */

  bindEvents() {
    /* INPUT */

    this.input.addEventListener("input", () => {
      const q = this.input.value.trim();

      if (!q) {
        this.clearResults();
        return;
      }

      this.debounce(() => this.search(q), 250);
    });

    /* CLICK EXTERIEUR */

    document.addEventListener("click", e => {
      if (!this.form.contains(e.target)) {
        this.clearResults();
      }
    });

    /* SCROLL INFINI */

    this.resultDiv.addEventListener("scroll", () => {
      if (
        this.resultDiv.scrollTop + this.resultDiv.clientHeight >=
        this.resultDiv.scrollHeight - 20
      ) {
        this.loadMore();
      }
    });
  }

  /* DEBOUNCE */

  debounce(callback, delay) {
    clearTimeout(this.debounceTimer);

    this.debounceTimer = setTimeout(callback, delay);
  }

  /* SEARCH AJAX */

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

  /* CREATION ITEM */

  createItem(item) {
    if (!item.id) {
      console.warn("FetchForm : item sans id", item);
      return null;
    }

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

    /* MODE LINKS */

    if (this.action === "links" && this.itemUrlPattern) {
      const url = this.itemUrlPattern.replace("__ID__", item.id);

      link.href = url;

      if (this.ajaxModal) {
        link.dataset.ajaxModal = "";
      }
    } else {
      link.href = "#";

      link.addEventListener("click", e => {
        e.preventDefault();

        this.selectItem(wrapper, label);
      });
    }

    wrapper.appendChild(link);

    return wrapper;
  }

  /* RENDER RESULTS */

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
      const node = this.createItem(item);

      if (node) {
        this.resultDiv.appendChild(node);
      }
    });

    this.resultDiv.classList.add("active");
  }

  /* SELECT ITEM */

  selectItem(item, label) {
    const id = item.dataset.id;

    if (this.action === "select") {
      this.input.value = label;

      if (this.hiddenInput) {
        this.hiddenInput.value = id;
      }

      this.clearResults();
    }
  }

  /* CLEAR RESULTS */

  clearResults() {
    this.resultDiv.innerHTML = "";
    this.resultDiv.classList.remove("active");
  }

  /* LOAD MORE */

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

      const items = this.normalizePayload(payload);

      items.forEach(item => {
        const node = this.createItem(item);

        if (node) {
          this.resultDiv.appendChild(node);
        }
      });
    } catch (e) {
      console.error("FetchForm loadMore error", e);
    }

    this.loadingMore = false;
  }
}
