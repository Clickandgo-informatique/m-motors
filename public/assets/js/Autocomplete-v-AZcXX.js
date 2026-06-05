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
      linkMode: this.linkMode,
      wrapper,
      dropdown: this.dropdown
    });

    this.input.addEventListener("input", () => this.onInput());

    document.addEventListener("click", e => {
      if (this.dropdown && !this.dropdown.contains(e.target) && e.target !== this.input) {
        this.close();
      }
    });
  }

  onInput() {
    const value = this.input.value.trim();

    this.log("INPUT", value);

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

    this.log("FETCH", url);

    try {
      const res = await fetch(url, {
        signal: this.abortController.signal,
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      this.log("STATUS", res.status);

      if (!res.ok) {
        console.error("[Autocomplete] réponse HTTP invalide", res.status);
        return;
      }

      const data = await res.json();

      this.log("RAW DATA", data);

      if (current !== this.requestId) {
        this.log("IGNORED OLD REQUEST");
        return;
      }

      const items = data.items || [];

      this.log("ITEMS PARSED", items);

      this.render(items, query);
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("[Autocomplete] fetch error", e);
      }
    }
  }

  render(items, query) {
    this.log("RENDER CALL", {
      itemsCount: items?.length,
      dropdown: this.dropdown
    });

    if (!this.dropdown) {
      console.error("[Autocomplete] dropdown null au render");
      return;
    }

    this.clear();

    if (!Array.isArray(items) || items.length === 0) {
      this.dropdown.innerHTML = "<div class='dropdown-item'>Aucun résultat</div>";
      this.open();
      return;
    }

    const frag = document.createDocumentFragment();

    items.forEach(item => {
      if (!item || !item.label) {
        console.warn("[Autocomplete] item invalide", item);
        return;
      }

      let element;

      if (this.linkMode === "1" && item.url) {
        element = document.createElement("a");
        element.href = item.url;
        element.className = "dropdown-item";
        element.innerHTML = this.highlight(item.label, query);
      } else {
        element = document.createElement("div");
        element.className = "dropdown-item";
        element.innerHTML = this.highlight(item.label, query);

        element.addEventListener("click", () => this.select(item));
      }

      frag.appendChild(element);
    });

    this.dropdown.appendChild(frag);

    this.log("RENDER DONE", this.dropdown.innerHTML);

    this.open();
  }

  highlight(text, query) {
    if (!query) return text;

    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const regex = new RegExp(`(${escaped})`, "gi");

    return text.replace(regex, "<strong>$1</strong>");
  }

  select(item) {
    this.log("SELECT", item);

    if (!item) return;

    const form = this.input.closest("form");

    if (!form) {
      console.error("[Autocomplete] form introuvable");
      return;
    }

    const hidden = form.querySelector("[data-autocomplete-value]");

    if (!hidden) {
      console.error("[Autocomplete] hidden input manquant");
      return;
    }

    if (!item.id || isNaN(item.id)) {
      console.warn("[Autocomplete] id invalide", item);
      return;
    }

    this.input.value = item.label || "";
    hidden.value = item.id;

    this.input.name = "q";

    form.requestSubmit();

    this.clear();
    this.close();
  }

  clear() {
    if (!this.dropdown) {
      console.warn("[Autocomplete] clear sur dropdown null");
      return;
    }

    this.dropdown.innerHTML = "";
  }

  open() {
    if (!this.dropdown) return;

    this.log("OPEN DROPDOWN");
    this.dropdown.style.display = "block";
  }

  close() {
    if (!this.dropdown) return;

    this.log("CLOSE DROPDOWN");
    this.dropdown.style.display = "none";
  }
}
