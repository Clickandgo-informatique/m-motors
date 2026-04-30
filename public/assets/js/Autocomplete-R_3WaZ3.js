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
    this.itemUrlTemplate = input.dataset.itemUrl || "";
    this.hiddenSelector = input.dataset.hiddenTarget || "";

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

    if (!wrapper) {
      console.warn("Autocomplete: wrapper introuvable");
      return;
    }

    this.dropdown = wrapper.querySelector(".dropdown-results");

    if (!this.dropdown) {
      console.warn("Autocomplete: dropdown introuvable");
      return;
    }

    this.dropdown.style.position = "absolute";
    this.dropdown.style.zIndex = "1000";
    this.dropdown.style.display = "none";
  }

  handleOutsideClick(e) {
    if (!this.dropdown || !this.input) return;

    const insideInput = this.input.contains(e.target);
    const insideDropdown = this.dropdown.contains(e.target);

    if (!insideInput && !insideDropdown) {
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

      this.render(items, query);
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("Autocomplete error:", e);
      }
    }
  }

  render(items, query) {
    this.clear();

    if (!items.length) {
      this.dropdown.innerHTML =
        "<div class='dropdown-no-results'>Aucun résultat</div>";
      this.open();
      return;
    }

    const fragment = document.createDocumentFragment();

    items.forEach(item => {
      const el = document.createElement("a");
      el.className = "dropdown-item";

      const href = this.itemUrlTemplate.replace("ID_PLACEHOLDER", item.id);
      el.href = href;

      el.appendChild(this.buildHighlightedLabel(item.label, query));

      el.addEventListener("click", e => {
        e.preventDefault();
        this.select(item);
        window.location.href = href;
      });

      fragment.appendChild(el);
    });

    this.dropdown.appendChild(fragment);
    this.open();
  }

  buildHighlightedLabel(text, query) {
    const span = document.createElement("span");

    if (!query) {
      span.textContent = text;
      return span;
    }

    const regex = new RegExp(`(${query})`, "gi");

    let lastIndex = 0;
    const matches = [...text.matchAll(regex)];

    if (!matches.length) {
      span.textContent = text;
      return span;
    }

    for (const match of matches) {
      const index = match.index;

      span.appendChild(document.createTextNode(text.slice(lastIndex, index)));

      const mark = document.createElement("mark");
      mark.textContent = match[0];

      span.appendChild(mark);

      lastIndex = index + match[0].length;
    }

    span.appendChild(document.createTextNode(text.slice(lastIndex)));

    return span;
  }

  select(item) {
    this.input.value = item.label;

    if (this.hiddenSelector) {
      const hidden = document.querySelector(this.hiddenSelector);
      if (hidden) hidden.value = item.id;
    }

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
