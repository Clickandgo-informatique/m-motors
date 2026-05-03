/**
 * Autocomplete.js
 * ------------------------------------------------------------------
 * Autocomplete AJAX
 *
 * Responsabilité :
 * - requêtes AJAX
 * - affichage dropdown
 * - gestion modale OU navigation
 * ------------------------------------------------------------------
 */

function debounce(fn, delay = 300) {
  let timer;

  return function(...args) {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(this, args), delay);
  };
}

export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    if (input.dataset.autocompleteInitialized === "1") return;
    input.dataset.autocompleteInitialized = "1";

    this.input = input;
    this.form = input.closest("form");

    this.url = input.dataset.url;

    if (!this.url) {
      console.error("[Autocomplete] data-url manquant");
      return;
    }

    this.isLinkMode = input.dataset.resultLinks === "true";

    this.itemUrlTemplate = this.form?.dataset.itemUrlTemplate || "";

    this.ajaxModal = this.form?.dataset.ajaxModal === "true";
    this.modalMode = this.form?.dataset.modalMode || "view";

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

    this.bindGlobalClick();
  }

  bindDropdown() {
    const wrapper = this.input.closest(".dropdown-wrapper");
    if (!wrapper) return;

    this.dropdown = wrapper.querySelector(".dropdown-results");
    if (!this.dropdown) return;

    this.dropdown.style.display = "none";
  }

  bindGlobalClick() {
    if (window.__autocompleteGlobalBound) return;

    window.__autocompleteGlobalBound = true;

    document.addEventListener("click", e => {
      if (e.target.closest("[data-ajax-modal]")) return;

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
    const current = this.requestId;

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

      if (current !== this.requestId) return;

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

        if (this.ajaxModal) {
          el.setAttribute("data-ajax-modal", "true");
          el.setAttribute("data-modal-mode", this.modalMode);
        }
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
