export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) {
      console.warn("[Autocomplete] input invalide");
      return;
    }

    this.input = input;

    this.url = input.dataset.url;
    this.linkMode = input.dataset.linkMode || "0";

    if (!this.url) {
      console.error("[Autocomplete] dataset.url manquant", input);
      return;
    }

    this.dropdown = null;
    this.abortController = null;
    this.requestId = 0;

    this.debug = true;

    this.init();
  }

  log(...args) {
    if (this.debug) console.log("[Autocomplete]", ...args);
  }

  init() {
    const wrapper = this.input.closest(".dropdown-wrapper");

    if (!wrapper) {
      console.error("[Autocomplete] wrapper .dropdown-wrapper introuvable", this.input);
      return;
    }

    this.dropdown = wrapper.querySelector(".dropdown-results");

    if (!this.dropdown) {
      console.error("[Autocomplete] dropdown .dropdown-results introuvable", wrapper);
      return;
    }

    this.log("INIT OK", {
      url: this.url,
      linkMode: this.linkMode
    });

    this.input.addEventListener("input", () => this.onInput());

    document.addEventListener("click", e => {
      if (e.target.closest(".dropzone")) return;

      if (this.dropdown && !this.dropdown.contains(e.target) && e.target !== this.input) {
        this.close();
      }
    });
  }

  onInput() {
    const value = this.input.value.trim();

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

    const url = `${this.url}?q=${encodeURIComponent(query)}&autocomplete=1`;

    try {
      const res = await fetch(url, {
        signal: this.abortController.signal,
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      if (!res.ok) return;

      const data = await res.json();

      if (current !== this.requestId) return;

      const items = data.items || [];

      this.render(items, query);
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("[Autocomplete] fetch error", e);
      }
    }
  }

  render(items, query) {
    if (!this.dropdown) return;

    this.clear();

    if (!Array.isArray(items) || items.length === 0) {
      this.dropdown.innerHTML = `
        <div class="dropdown-item dropdown-empty">Aucun résultat</div>
      `;

      this.open();
      return;
    }

    const frag = document.createDocumentFragment();

    items.forEach(item => {
      if (!item?.label) return;

      let element;

      if (this.linkMode === "1" && item.url) {
        element = document.createElement("a");
        element.href = item.url;
        element.className = "dropdown-item";
        element.innerHTML = this.highlight(item.label, query);
        element.dataset.ajaxModal = "true";
      } else {
        element = document.createElement("div");
        element.className = "dropdown-item";
        element.innerHTML = this.highlight(item.label, query);

        element.addEventListener("click", () => this.select(item));
      }

      frag.appendChild(element);
    });

    this.dropdown.appendChild(frag);

    this.open();
  }

  highlight(text, query) {
    if (!query) return text;

    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const regex = new RegExp(`(${escaped})`, "gi");

    return text.replace(regex, "<strong>$1</strong>");
  }

  select(item) {
    if (!item) return;

    const form = this.input.closest("form");

    if (!form) return;

    const hidden = form.querySelector("[data-autocomplete-value]");

    if (!hidden || !item.id) return;

    this.input.value = item.label || "";
    hidden.value = item.id;

    this.input.name = "q";

    form.requestSubmit();

    this.clear();
    this.close();
  }

  clear() {
    if (!this.dropdown) return;

    this.dropdown.innerHTML = "";
  }

  open() {
    if (!this.dropdown) return;

    this.dropdown.classList.add("is-active");
  }

  close() {
    if (!this.dropdown) return;

    this.dropdown.classList.remove("is-active");
  }
}
