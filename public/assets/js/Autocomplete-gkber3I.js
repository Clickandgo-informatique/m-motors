export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;

    this.url = input.dataset.url;
    this.linkMode = input.dataset.linkMode || "0";

    this.dropdown = null;
    this.abortController = null;
    this.requestId = 0;

    if (!this.url) return;

    this.init();
  }

  init() {
    const wrapper = this.input.closest(".dropdown-wrapper");

    if (!wrapper) return;

    this.dropdown = wrapper.querySelector(".dropdown-results");

    if (!this.dropdown) return;

    this.input.addEventListener("input", () => this.onInput());

    document.addEventListener("click", e => {
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

    const url = `${this.url}?q=${encodeURIComponent(query)}`;

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

      this.render(data.items || [], query);
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("[Autocomplete] fetch error", e);
      }
    }
  }

  render(items, query) {
    this.clear();

    if (!Array.isArray(items) || !items.length) {
      this.dropdown.innerHTML = "<div class='dropdown-item'>Aucun résultat</div>";
      this.open();
      return;
    }

    const frag = document.createDocumentFragment();

    items.forEach(item => {
      if (!item || !item.label) return;

      const div = document.createElement("div");
      div.className = "dropdown-item";

      div.innerHTML = this.highlight(item.label, query);

      div.addEventListener("click", () => this.select(item));

      frag.appendChild(div);
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
    if (!hidden) return;

    // =========================
    // FIX CRITIQUE ICI
    // =========================
    if (!item.id || isNaN(item.id)) {
      console.warn("[Autocomplete] id invalide bloqué", item);
      return;
    }

    this.input.value = item.label || "";
    hidden.value = item.id;

    this.input.name = "q";

    if (this.linkMode === "1" && item.url) {
      window.location.href = item.url;
      return;
    }

    form.requestSubmit();

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
