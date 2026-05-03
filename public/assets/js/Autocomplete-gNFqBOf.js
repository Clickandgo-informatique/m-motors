/**
 * assets/js/Autocomplete.js
 * ------------------------------------------------------------------
 * VERSION STABLE
 * ------------------------------------------------------------------
 */

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

    /**
     * 🔴 FIX CRITIQUE
     * Protection contre dataset manquant (source du /undefined)
     */
    if (!this.url) {
      console.error("[Autocomplete] data-url manquant sur input", input);
      return;
    }

    this.isLinkMode = input.dataset.resultLinks === "true";
    this.itemUrlTemplate = input.dataset.itemUrl || "";

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

    /**
     * listener global protégé (évite duplication après ui:updated)
     */
    if (!window.__autocompleteGlobalBound) {
      window.__autocompleteGlobalBound = true;

      document.addEventListener("click", e => {
        document
          .querySelectorAll("[data-autocomplete-initialized='1']")
          .forEach(input => {
            const wrapper = input.closest(".dropdown-wrapper");
            const dropdown = wrapper?.querySelector(".dropdown-results");

            if (!dropdown) return;

            if (!dropdown.contains(e.target) && !input.contains(e.target)) {
              dropdown.style.display = "none";
            }
          });
      });
    }
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

  onInput() {
    const value = (this.input.value || "").trim();

    if (value.length < 2) {
      this.clear();
      this.close();
      return;
    }

    this.fetch(value);
  }

  async fetch(query) {
    this.requestId++;
    const currentRequest = this.requestId;

    if (this.abortController) {
      this.abortController.abort();
    }

    this.abortController = new AbortController();

    try {
      const res = await fetch(
        `${this.url}?mode=autocomplete&q=${encodeURIComponent(query)}`,
        { signal: this.abortController.signal }
      );

      const data = await res.json();

      if (currentRequest !== this.requestId) return;

      const items = Array.isArray(data.items) ? data.items : [];

      this.render(items);
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error(e);
      }
    }
  }

  render(items) {
    this.clear();

    if (!items.length) {
      this.dropdown.innerHTML =
        "<div class='dropdown-no-results'>Aucun résultat</div>";
      this.open();
      return;
    }

    const fragment = document.createDocumentFragment();

    items.forEach(item => {
      const el = document.createElement(this.isLinkMode ? "a" : "div");

      el.className = "dropdown-item";
      el.textContent = item.label;

      if (this.isLinkMode) {
        const url = this.itemUrlTemplate.replace("ID_PLACEHOLDER", item.id);

        el.href = url;

        el.addEventListener("click", e => {
          e.preventDefault();
          window.location.href = url;
        });
      } else {
        el.addEventListener("click", () => this.select(item));
      }

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
