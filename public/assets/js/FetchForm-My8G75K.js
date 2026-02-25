console.log("FetchForm.js instancié");

export default class FetchForm {
  constructor(input) {
    this.input = input;
    this.form = input.closest("form");

    if (!this.form) {
      console.warn("FetchForm: input hors d'un <form>", input);
      return;
    }

    /* ----------------------------- */
    /* CONFIG VIA DATASET (GÉNÉRIQUE) */
    /* ----------------------------- */
    this.endpoint = this.form.dataset.searchForm;

    this.dropdownClass = this.form.dataset.dropdownClass;
    this.itemClass = this.form.dataset.itemClass;
    this.linkClass = this.form.dataset.linkClass;
    this.noResultsClass = this.form.dataset.noResultsClass;
    this.urlField = this.form.dataset.urlField;
    this.itemUrlPattern = this.form.dataset.itemUrl;

    this.resultDivSelector = input.dataset.resultDiv;
    this.resultDiv = document.querySelector(this.resultDivSelector);

    this.autocomplete = input.dataset.autocomplete === "true";
    this.defaultSuggestions = input.dataset.defaultSuggestions === "true";
    this.highlight = input.dataset.highlight === "true";

    this.toggleBtn = this.form.querySelector("[data-search-toggle]");

    this.activeIndex = -1;
    this.debounceTimer = null;
    this.page = 1;

    if (!this.endpoint || !this.resultDiv) {
      console.warn("FetchForm: configuration incomplète", input);
      return;
    }

    this.injectSpinner();
    this.bindEvents();
  }

  /* ----------------------------- */
  /* SPINNER */
  /* ----------------------------- */
  injectSpinner() {
    this.spinner = document.createElement("div");
    this.spinner.classList.add("dropdown-spinner");
    this.spinner.innerHTML = `<div class="spinner-circle"></div>`;
    this.resultDiv.appendChild(this.spinner);
  }

  showSpinner() {
    this.spinner.classList.add("visible");
  }

  hideSpinner() {
    this.spinner.classList.remove("visible");
  }

  /* ----------------------------- */
  /* EVENTS */
  /* ----------------------------- */
  bindEvents() {
    /* Input */
    this.input.addEventListener("input", () => {
      this.updateToggleButton();

      const q = this.input.value.trim();

      if (!q && !this.defaultSuggestions) {
        this.clearResults();
        return;
      }

      this.debounce(() => this.search(q), 250);
    });

    /* Bouton toggle (loupe → croix) */
    if (this.toggleBtn) {
      this.toggleBtn.addEventListener("click", () => {
        if (this.input.value.trim() !== "") {
          this.input.value = "";
          this.clearResults();
          this.updateToggleButton();
          this.input.focus();
        } else {
          this.input.focus();
        }
      });
    }

    /* Navigation clavier */
    this.input.addEventListener("keydown", e => {
      const items = this.resultDiv.querySelectorAll(`.${this.itemClass}`);
      if (!items.length) return;

      if (e.key === "ArrowDown") {
        e.preventDefault();
        this.activeIndex = (this.activeIndex + 1) % items.length;
        this.updateActiveItem(items);
      }

      if (e.key === "ArrowUp") {
        e.preventDefault();
        this.activeIndex = (this.activeIndex - 1 + items.length) % items.length;
        this.updateActiveItem(items);
      }

      if (e.key === "Enter") {
        e.preventDefault();
        const link = items[this.activeIndex]?.querySelector(
          `.${this.linkClass}`
        );
        if (link) window.location.href = link.href;
      }
    });

    /* Fermer si clic ailleurs */
    document.addEventListener("click", e => {
      if (!this.form.contains(e.target)) {
        this.clearResults();
      }
    });
  }

  /* ----------------------------- */
  /* TOGGLE BUTTON */
  /* ----------------------------- */
  updateToggleButton() {
    if (!this.toggleBtn) return;

    if (this.input.value.trim() === "") {
      this.toggleBtn.textContent = "🔍";
    } else {
      this.toggleBtn.textContent = "✖";
    }
  }

  /* ----------------------------- */
  /* DEBOUNCE */
  /* ----------------------------- */
  debounce(callback, delay) {
    clearTimeout(this.debounceTimer);
    this.debounceTimer = setTimeout(callback, delay);
  }

  /* ----------------------------- */
  /* SEARCH */
  /* ----------------------------- */
  async search(q) {
    this.hasSearched = true;
    this.page = 1;

    this.resultDiv.classList.add("active");
    this.showSpinner();

    const url = `${this.endpoint}?${this.input.name}=${encodeURIComponent(q)}`;

    try {
      const response = await fetch(url);
      const data = await response.json();
      this.renderResults(data, q);
    } catch (e) {
      console.error("FetchForm error:", e);
    } finally {
      this.hideSpinner();
    }
  }

  /* ----------------------------- */
  /* RENDER RESULTS */
  /* ----------------------------- */
  renderResults(data, q) {
    this.resultDiv.innerHTML = "";
    this.resultDiv.appendChild(this.spinner);

    /* Compteur */
    const count = document.createElement("div");
    count.classList.add("dropdown-count");
    count.textContent = `${data.length} résultat${data.length > 1 ? "s" : ""}`;
    this.resultDiv.appendChild(count);

    if (!data.length) {
      this.resultDiv.innerHTML += `<div class="${this.noResultsClass}">Aucun résultat</div>`;
      this.resultDiv.classList.add("active");
      return;
    }

    this.activeIndex = -1;

    data.forEach(item => {
      const div = document.createElement("div");
      div.classList.add(this.itemClass);

      /* Texte affiché */
      let text = Object.entries(item)
        .filter(
          ([key, val]) => typeof val === "string" && key !== this.urlField
        )
        .map(([_, val]) => val)
        .join(" ");

      if (this.highlight && q) {
        const regex = new RegExp(`(${q})`, "gi");
        text = text.replace(regex, "<mark>$1</mark>");
      }

      /* URL dynamique */
      const url = this.itemUrlPattern.replace("__ID__", item.id);

      div.innerHTML = `
        <a href="${url}" class="${this.linkClass}">
          ${text}
        </a>
      `;

      this.resultDiv.appendChild(div);
    });
  }

  /* ----------------------------- */
  /* ACTIVE ITEM (CLAVIER) */
  /* ----------------------------- */
  updateActiveItem(items) {
    items.forEach((item, index) => {
      item.classList.toggle("active", index === this.activeIndex);
    });

    const activeLink = items[this.activeIndex]?.querySelector(
      `.${this.linkClass}`
    );
    if (activeLink) activeLink.scrollIntoView({ block: "nearest" });
  }

  /* ----------------------------- */
  /* CLEAR */
  /* ----------------------------- */
  clearResults() {
    if (this.hasSearched) {
      this.resultDiv.innerHTML = "";
      this.resultDiv.classList.remove("active");
      this.activeIndex = -1;
    }
  }
}
