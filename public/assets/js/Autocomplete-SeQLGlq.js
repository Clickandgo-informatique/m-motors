export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    if (input.dataset.autocompleteInitialized === "1") return;
    input.dataset.autocompleteInitialized = "1";

    this.input = input;
    this.url = input.dataset.url;
    this.dropdown = null;
    this.timer = null;

    if (!this.url) return;

    this.init();
  }

  init() {
    const wrapper = this.input.closest(".dropdown-wrapper");
    this.dropdown = wrapper?.querySelector(".dropdown-results");

    this.input.addEventListener("input", () => this.debounce());

    document.addEventListener("click", e => {
      if (!this.dropdown) return;

      if (!this.dropdown.contains(e.target) && !this.input.contains(e.target)) {
        this.close();
      }
    });
  }

  debounce() {
    clearTimeout(this.timer);

    this.timer = setTimeout(() => {
      this.fetch();
    }, 200);
  }

  fetch() {
    const q = this.input.value.trim();

    if (q.length < 2) {
      this.clear();
      return;
    }

    fetch(`${this.url}?q=${encodeURIComponent(q)}`)
      .then(r => r.json())
      .then(data => this.render(data.items || []));
  }

  render(items) {
    this.clear();

    if (!items.length) {
      this.dropdown.innerHTML = "Aucun résultat";
      return;
    }

    items.forEach(item => {
      const el = document.createElement("div");
      el.className = "dropdown-item";
      el.textContent = item.label;

      el.addEventListener("click", () => {
        this.input.value = item.label;
        this.clear();
      });

      this.dropdown.appendChild(el);
    });
  }

  clear() {
    if (this.dropdown) this.dropdown.innerHTML = "";
  }

  close() {
    if (this.dropdown) this.dropdown.style.display = "none";
  }
}
