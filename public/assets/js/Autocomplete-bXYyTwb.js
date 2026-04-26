
/**
 * Debounce simple
 */
function debounce(fn, delay = 300) {
  let timer;

  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(null, args), delay);
  };
}

export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url;
    this.itemUrlTemplate = input.dataset.itemUrl || "";
    this.hiddenSelector = input.dataset.hiddenTarget || "";

    this.page = 1;
    this.loading = false;
    this.dropdown = null;

    // contrôle requêtes
    this.abortController = null;

    // IMPORTANT : évite les injections après navigation
    this.active = true;

    if (!this.url) {
      console.error("Autocomplete: URL manquante");
      return;
    }

    this.initDropdown();
    this.attachEvents();
  }

  /**
   * Création dropdown
   */
  initDropdown() {
    this.dropdown = document.createElement("div");
    this.dropdown.className = "dropdown-results";

    this.dropdown.style.position = "absolute";
    this.dropdown.style.zIndex = "1000";
    this.dropdown.style.display = "none";

    if (this.input.parentNode) {
      const style = getComputedStyle(this.input.parentNode);

      if (style.position === "static") {
        this.input.parentNode.style.position = "relative";
      }

      this.input.parentNode.appendChild(this.dropdown);
    }
  }

  /**
   * Events + debounce + seuil min
   */
  attachEvents() {
    this.input.addEventListener(
      "input",
      debounce(() => {
        const value = this.input.value.trim();

        if (value.length < 2) {
          this.page = 1;
          this.clear();
          this.close();
          return;
        }

        this.page = 1;
        this.fetch();
      }, 300)
    );

    document.addEventListener("click", (e) => {
      if (!this.dropdown.contains(e.target) && e.target !== this.input) {
        this.close();
      }
    });
  }

  /**
   * FETCH sécurisé
   */
  async fetch() {
    if (!this.active) return;

    this.loading = true;

    if (this.abortController) {
      this.abortController.abort();
    }

    this.abortController = new AbortController();

    try {
      const res = await fetch(
        `${this.url}?autocomplete=1&q=${encodeURIComponent(this.input.value)}&page=${this.page}`,
        { signal: this.abortController.signal }
      );

      const text = await res.text();

      let data;

      try {
        data = JSON.parse(text);
      } catch (e) {
        console.error("Réponse non JSON (probable HTML backend) :", text);
        return;
      }

      if (!this.active) return;

      const items = Array.isArray(data.items) ? data.items : [];

      if (!items.length) {
        this.clear();
        this.dropdown.innerHTML =
          "<div class='dropdown-no-results'>Aucun résultat</div>";
        this.open();
        return;
      }

      this.render(items);

    } catch (err) {
      if (err.name !== "AbortError") {
        console.error("Autocomplete error:", err);
      }
    } finally {
      this.loading = false;
    }
  }

  /**
   * Rendu strict des items uniquement
   */
  render(items) {
    if (!this.active) return;
    if (!Array.isArray(items)) return;

    this.clear();

    const fragment = document.createDocumentFragment();

    items.forEach(item => {
      const el = document.createElement("a");

      el.className = "dropdown-item";
      el.href = this.itemUrlTemplate.replace("ID_PLACEHOLDER", item.id);
      el.textContent = item.label;

      el.addEventListener("click", (e) => {
        e.preventDefault();

        this.select(item);

        window.location.href = el.href;
      });

      fragment.appendChild(el);
    });

    this.dropdown.appendChild(fragment);
    this.open();
  }

  /**
   * Sélection item
   */
  select(item) {
    this.input.value = item.label;

    if (this.hiddenSelector) {
      const hidden = document.querySelector(this.hiddenSelector);
      if (hidden) hidden.value = item.id;
    }

    // IMPORTANT : stop total du composant après sélection
    this.active = false;

    if (this.abortController) {
      this.abortController.abort();
    }

    this.close();
  }

  /**
   * UI helpers
   */
  clear() {
    this.dropdown.innerHTML = "";
  }

  open() {
    this.dropdown.style.display = "block";
  }

  close() {
    this.dropdown.style.display = "none";
  }
}