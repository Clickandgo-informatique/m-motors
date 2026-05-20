export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) {
      console.warn("[Autocomplete] input invalide", input);
      return;
    }

    this.input = input;

    this.url = input.dataset.url;
    this.linkMode = input.dataset.linkMode || "0";

    this.dropdown = null;
    this.abortController = null;
    this.requestId = 0;

    // =========================
    // DATASET CHECKS
    // =========================
    if (!this.url) {
      console.warn("[Autocomplete] data-url manquant", this.input);
      return;
    }

    if (!this.input.dataset.linkMode) {
      console.warn("[Autocomplete] data-link-mode absent, fallback = 0", this.input);
    }

    this.init();
  }

  init() {
    const wrapper = this.input.closest(".dropdown-wrapper");

    if (!wrapper) {
      console.warn("[Autocomplete] .dropdown-wrapper introuvable", this.input);
      return;
    }

    this.dropdown = wrapper.querySelector(".dropdown-results");

    if (!this.dropdown) {
      console.warn("[Autocomplete] .dropdown-results introuvable", wrapper);
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

    // =========================
    // URL CHECK
    // =========================
    if (!this.url) {
      console.warn("[Autocomplete] impossible de fetch : url absente");
      return;
    }

    const url = `${this.url}?q=${encodeURIComponent(query)}`;

    console.log("[Autocomplete] fetch", url);

    try {
      const res = await fetch(url, {
        signal: this.abortController.signal,
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      if (!res.ok) {
        console.error("[Autocomplete] réponse HTTP invalide", res.status);
        return;
      }

      const data = await res.json();

      console.log("[Autocomplete] réponse", data);

      if (current !== this.requestId) {
        console.warn("[Autocomplete] réponse ignorée (requête obsolète)");
        return;
      }

      if (!Array.isArray(data.items)) {
        console.warn("[Autocomplete] data.items absent ou invalide", data);
      }

      this.render(data.items || [], query);
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("[Autocomplete] erreur fetch", e);
      }
    }
  }

  render(items, query) {
    this.clear();

    if (!Array.isArray(items)) {
      console.error("[Autocomplete] render attend un tableau", items);
      return;
    }

    if (!items.length) {
      this.dropdown.innerHTML = `
        <div class="dropdown-item">
          Aucun résultat
        </div>
      `;

      this.open();
      return;
    }

    const frag = document.createDocumentFragment();

    items.forEach(item => {
      // =========================
      // ITEM CHECKS
      // =========================
      if (!item.label) {
        console.warn("[Autocomplete] item.label manquant", item);
      }

      if (this.linkMode === "1" && !item.url) {
        console.warn("[Autocomplete] item.url manquant en linkMode", item);
      }

      const content = this.highlight(item.label || "", query);

      // =========================
      // LINK MODE
      // =========================
      if (this.linkMode === "1" && item.url) {
        const a = document.createElement("a");

        a.className = "dropdown-item";
        a.href = item.url;
        a.innerHTML = content;

        a.addEventListener("click", e => {
          e.preventDefault();

          this.select(item);
        });

        frag.appendChild(a);

        return;
      }

      // =========================
      // DEFAULT MODE
      // =========================
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
    if (!query) {
      return text;
    }

    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");

    const regex = new RegExp(`(${escaped})`, "gi");

    return text.replace(regex, "<strong>$1</strong>");
  }

  select(item) {
    if (!item) {
      console.warn("[Autocomplete] item absent dans select()");
      return;
    }

    this.input.value = item.label || "";

    const form = this.input.closest("form");

    if (!form) {
      console.warn("[Autocomplete] form introuvable", this.input);
      return;
    }

    const hidden = form.querySelector("[data-autocomplete-value]");

    if (!hidden) {
      console.warn("[Autocomplete] champ hidden [data-autocomplete-value] introuvable", form);
    }

    if (hidden) {
      hidden.value = item.id || "";

      // =========================
      // WARNING HARD CODE
      // =========================
      console.warn("[Autocomplete] hidden.name forcé à vehicleId");

      hidden.name = "vehicleId";
    }

    this.input.name = "q";

    // =========================
    // LINK MODE NAVIGATION
    // =========================
    if (this.linkMode === "1" && item.url) {
      window.location.href = item.url;

      return;
    }

    console.log("[Autocomplete] submit form");

    form.requestSubmit();

    this.clear();
    this.close();
  }

  clear() {
    if (!this.dropdown) {
      console.warn("[Autocomplete] clear() sans dropdown");
      return;
    }

    this.dropdown.innerHTML = "";
  }

  open() {
    if (!this.dropdown) {
      console.warn("[Autocomplete] open() sans dropdown");
      return;
    }

    this.dropdown.style.display = "block";
  }

  close() {
    if (!this.dropdown) {
      console.warn("[Autocomplete] close() sans dropdown");
      return;
    }

    this.dropdown.style.display = "none";
  }
}
