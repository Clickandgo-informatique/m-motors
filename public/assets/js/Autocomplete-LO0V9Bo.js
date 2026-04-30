// assets/js/Autocomplete.js

function debounce(fn, delay = 300) {
  let timer;

  return function(...args) {
    clearTimeout(timer);

    timer = setTimeout(() => {
      fn.apply(this, args);
    }, delay);
  };
}

export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    if (input.dataset.autocompleteInitialized === "1") return;
    input.dataset.autocompleteInitialized = "1";

    this.input = input;
    this.url = input.dataset.url;

    this.dropdown = null;

    this.abortController = null;
    this.requestId = 0;

    this.init();
  }

  init() {
    this.bindDropdown();

    this.input.addEventListener(
      "input",
      debounce(this.onInput.bind(this), 300)
    );

    document.addEventListener("click", e => this.handleOutsideClick(e));
  }

  bindDropdown() {
    const wrapper = this.input.closest(".dropdown-wrapper");
    if (!wrapper) return;

    this.dropdown = wrapper.querySelector(".dropdown-results");
    if (!this.dropdown) return;

    this.dropdown.style.position = "absolute";
    this.dropdown.style.zIndex = "1000";
    this.dropdown.style.display = "none";
  }

  handleOutsideClick(e) {
    if (!this.dropdown) return;

    if (!this.dropdown.contains(e.target) && !this.input.contains(e.target)) {
      this.close();
    }
  }

  onInput() {
    const value = (this.input.value || "").trim();

    // CONDITION 1 : champ vide → reset immédiat
    if (value.length === 0) {
      this.clear();
      this.close();
      this.abortRequest();
      return;
    }

    // CONDITION 2 : moins de 2 caractères → reset + pas de requête
    if (value.length < 2) {
      this.clear();
      this.close();
      return;
    }

    // OK → requête
    this.fetch(value);
  }

  abortRequest() {
    if (this.abortController) {
      this.abortController.abort();
    }
  }

  async fetch(query) {
    this.requestId++;
    const currentRequest = this.requestId;

    this.abortRequest();
    this.abortController = new AbortController();

    try {
      const res = await fetch(
        `${this.url}?mode=autocomplete&q=${encodeURIComponent(query)}`,
        { signal: this.abortController.signal }
      );

      const data = await res.json();

      if (currentRequest !== this.requestId) return;

      const items = Array.isArray(data.items) ? data.items : [];

      this.render(items, query);
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("Autocomplete error:", e);
      }
    }
  }

  render(items, query) {
    this.clear();

    // vide → reset visuel immédiat
    if (!items.length) {
      this.dropdown.innerHTML =
        "<div class='dropdown-no-results'>Aucun résultat</div>";
      this.open();
      return;
    }

    const fragment = document.createDocumentFragment();

    items.forEach(item => {
      const el = document.createElement("a");
      el.href = "#";
      el.className = "dropdown-item";

      el.textContent = item.label;

      el.addEventListener("click", e => {
        e.preventDefault();
        this.select(item);
      });

      fragment.appendChild(el);
    });

    this.dropdown.appendChild(fragment);
    this.open();
  }

  select(item) {
    this.input.value = item.label;
    this.clear();
    this.close();
  }

  clear() {
    if (this.dropdown) this.dropdown.innerHTML = "";
  }

  open() {
    if (this.dropdown) this.dropdown.style.display = "block";
  }

  close() {
    if (this.dropdown) this.dropdown.style.display = "none";
  }
}
