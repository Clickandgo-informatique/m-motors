export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) {
      console.warn("[Autocomplete] input invalide");
      return;
    }

    this.input = input;

    this.url = input.dataset.url;
    this.context = input.dataset.context || "default";

    this.dropdown = null;
    this.abortController = null;
    this.requestId = 0;

    if (!this.url) {
      console.warn("[Autocomplete] data-url manquant");
      return;
    }

    this.init();
  }

  init() {
    const wrapper = this.input.closest(".dropdown-wrapper");

    if (!wrapper) {
      console.warn("[Autocomplete] wrapper introuvable");
      return;
    }

    this.dropdown = wrapper.querySelector(".dropdown-results");

    if (!this.dropdown) {
      console.warn("[Autocomplete] dropdown introuvable");
      return;
    }

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

      if (!res.ok) {
        console.error("[Autocomplete] HTTP error", res.status);
        return;
      }

      const data = await res.json();

      if (current !== this.requestId) {
        return;
      }

      this.render(data.items || [], query);
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("[Autocomplete] fetch error", e);
      }
    }
  }

  render(items, query) {
    this.clear();

    if (!Array.isArray(items)) {
      console.error("[Autocomplete] items invalid");
      return;
    }

    if (!items.length) {
      this.dropdown.innerHTML = "<div class='dropdown-item disabled'>Aucun résultat</div>";
      this.open();
      return;
    }

    const frag = document.createDocumentFragment();

    items.forEach(item => {
      if (!item || !item.label) return;

      const content = this.highlight(item.label, query);

      const div = document.createElement("div");
      div.className = "dropdown-item";
      div.innerHTML = content;

      div.addEventListener("click", () => {
        this.select(item);
      });

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
    if (!item || typeof item.id === "undefined") {
      console.warn("[Autocomplete] item invalide");
      return;
    }

    // sécurité anti injection de string type "search"
    if (isNaN(item.id)) {
      console.warn("[Autocomplete] id invalide bloqué", item.id);
      return;
    }

    const form = this.input.closest("form");

    if (!form) {
      console.warn("[Autocomplete] form introuvable");
      return;
    }

    this.input.value = item.label || "";

    const hidden = form.querySelector(`[data-autocomplete-value="${this.context}"]`);

    if (!hidden) {
      console.warn("[Autocomplete] hidden field introuvable");
      return;
    }

    hidden.value = item.id;

    this.input.name = "q";

    if (this.input.dataset.linkMode === "1" && item.url) {
      window.location.href = item.url;
      return;
    }

    form.requestSubmit();

    this.clear();
    this.close();
  }

  clear() {
    if (this.dropdown) {
      this.dropdown.innerHTML = "";
    }
  }

  open() {
    if (this.dropdown) {
      this.dropdown.style.display = "block";
    }
  }

  close() {
    if (this.dropdown) {
      this.dropdown.style.display = "none";
    }
  }
}
