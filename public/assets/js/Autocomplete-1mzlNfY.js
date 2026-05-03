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

    if (input.dataset.initialized === "1") return;
    input.dataset.initialized = "1";

    this.input = input;
    this.url = input.dataset.url;

    this.linkMode = input.dataset.linkMode === "1";
    this.baseUrl = input.dataset.baseUrl || null;

    this.dropdown = null;
    this.abortController = null;
    this.requestId = 0;

    if (!this.url) return;

    this.init();
  }

  init() {
    console.log("Autocomplete.js initialisé", this.input);

    const wrapper = this.input.closest(".dropdown-wrapper");
    this.dropdown = wrapper?.querySelector(".dropdown-results");

    this.input.addEventListener(
      "input",
      debounce(() => {
        console.log("[Autocomplete] input event fired", this.input.value);
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
    const current = this.requestId;

    if (this.abortController) {
      this.abortController.abort();
    }

    this.abortController = new AbortController();

    try {
      const res = await fetch(`${this.url}?q=${encodeURIComponent(query)}`, {
        signal: this.abortController.signal
      });

      const data = await res.json();

      if (current !== this.requestId) return;

      this.render(data.items || []);
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("[Autocomplete]", e);
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

    const frag = document.createDocumentFragment();

    items.forEach(item => {
      const el = document.createElement(this.linkMode ? "a" : "div");

      el.className = "dropdown-item";
      el.textContent = item.label;

      if (this.linkMode && this.baseUrl) {
        const url = `${this.baseUrl}${item.id}/edit`;

        el.href = url;

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

      frag.appendChild(el);
    });

    this.dropdown.appendChild(frag);
    this.open();
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
