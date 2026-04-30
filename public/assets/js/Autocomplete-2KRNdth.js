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

    // FLAG IMPORTANT
    this.isLinkMode = input.dataset.resultLinks === "true";

    // template URL (si mode link)
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
        el.href = this.itemUrlTemplate.replace("ID_PLACEHOLDER", item.id);

        // navigation directe
        el.addEventListener("click", () => {
          window.location.href = el.href;
        });
      } else {
        el.addEventListener("click", () => {
          this.select(item);
        });
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
