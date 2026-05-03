// Autocomplete.js

function debounce(fn, delay = 250) {
  let timer;

  return (...args) => {
    clearTimeout(timer);

    timer = setTimeout(() => {
      fn(...args);
    }, delay);
  };
}

export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    if (input.dataset.initialized === "1") return;
    input.dataset.initialized = "1";

    this.input = input;
    this.url = input.dataset.url;

    this.linkMode = input.dataset.linkMode === "1";
    this.baseUrl = input.dataset.baseUrl || "";

    this.dropdown = null;
    this.abortController = null;
    this.requestId = 0;

    if (!this.url) {
      console.error("[Autocomplete] data-url manquant", input);
      return;
    }

    this.init();
  }

  init() {
    console.log("[Autocomplete] INIT OK", this.input);

    const wrapper = this.input.closest(".dropdown-wrapper");
    this.dropdown = wrapper?.querySelector(".dropdown-results");

    if (!this.dropdown) {
      console.warn("[Autocomplete] dropdown introuvable");
    }

    this.input.addEventListener(
      "input",
      debounce(e => {
        this.onInput();
      }, 250)
    );

    document.addEventListener("click", e => {
      if (!this.dropdown) return;

      if (!this.dropdown.contains(e.target) && !this.input.contains(e.target)) {
        this.close();
      }
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
    const currentRequest = this.requestId;

    if (this.abortController) {
      this.abortController.abort();
    }

    this.abortController = new AbortController();

    try {
      const res = await fetch(`${this.url}?q=${encodeURIComponent(query)}`, {
        signal: this.abortController.signal,
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      const data = await res.json();

      if (currentRequest !== this.requestId) return;

      this.render(data.items || []);
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("[Autocomplete] fetch error", e);
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
      const el = document.createElement(this.linkMode ? "a" : "div");

      el.className = "dropdown-item";
      el.textContent = item.label;

      if (this.linkMode && this.baseUrl) {
        const url = `${this.baseUrl}${item.id}/edit`;

        el.href = url;

        /*
         * IMPORTANT :
         * compatibilité AjaxManager (modale)
         */
        el.setAttribute("data-ajax-modal", url);

        el.addEventListener("click", e => {
          e.preventDefault();
          window.location.href = url;
        });
      } else {
        el.addEventListener("click", () => {
          this.input.value = item.label;
          this.clear();
          this.close();
        });
      }

      fragment.appendChild(el);
    });

    this.dropdown.appendChild(fragment);
    this.open();
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
