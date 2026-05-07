export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url;

    this.dropdown = null;
    this.abortController = null;
    this.requestId = 0;

    if (!this.url) return;

    this.init();
  }

  init() {
    const wrapper = this.input.closest(".dropdown-wrapper");
    this.dropdown = wrapper?.querySelector(".dropdown-results");

    if (!this.dropdown) return;

    this.input.addEventListener("input", () => this.onInput());

    document.addEventListener("click", e => {
      if (!this.dropdown.contains(e.target) && e.target !== this.input) {
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

    console.log("[Autocomplete] fetch:", url);

    try {
      const res = await fetch(url, {
        signal: this.abortController.signal,
        headers: { "X-Requested-With": "XMLHttpRequest" }
      });

      const data = await res.json();

      if (current !== this.requestId) return;

      this.render(data.items || []);
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("[Autocomplete] error", e);
      }
    }
  }

  render(items) {
    this.clear();

    if (!items.length) {
      this.dropdown.innerHTML = "<div>Aucun résultat</div>";
      this.open();
      return;
    }

    const frag = document.createDocumentFragment();

    items.forEach(item => {
      const div = document.createElement("div");
      div.className = "dropdown-item";
      div.textContent = item.label;

      div.addEventListener("click", () => {
        this.select(item);
      });

      frag.appendChild(div);
    });

    this.dropdown.appendChild(frag);
    this.open();
  }

  select(item) {
    console.log("[Autocomplete] select", item);

    this.input.value = item.label;

    const form = this.input.closest("form");

    // reset propre
    const hidden = form.querySelector("[data-autocomplete-value]");
    const textInput = this.input;

    if (hidden) {
      hidden.value = item.id; // UNIQUEMENT ID
      hidden.name = "vehicleId"; // IMPORTANT: on force le backend
    }

    // IMPORTANT: éviter double query param
    textInput.name = "q";

    form.requestSubmit();
  }

  clear() {
    if (this.dropdown) this.dropdown.innerHTML = "";
  }

  open() {
    this.dropdown.style.display = "block";
  }

  close() {
    this.dropdown.style.display = "none";
  }
}
