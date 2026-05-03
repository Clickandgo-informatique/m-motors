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
    console.log("autocomplete.js chargé");

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
      this.dropdown.innerHTML =
        "<div class='dropdown-no-results'>Aucun résultat</div>";
      this.open();
      return;
    }

    const fragment = document.createDocumentFragment();

    items.forEach(item => {
      const isLink = this.isLinkMode;

      const el = document.createElement(isLink ? "a" : "div");

      el.className = "dropdown-item";
      el.textContent = item.label;

      if (isLink) {
        const url = this.itemUrlTemplate
          ? this.itemUrlTemplate.replace("ID_PLACEHOLDER", item.id)
          : "#";

        el.href = url;

        el.addEventListener("click", e => {
          e.preventDefault();
          window.location.href = url;
        });

        el.style.cursor = "pointer";
      } else {
        el.addEventListener("click", () => this.select(item));
      }

      fragment.appendChild(el);
    });

    this.dropdown.appendChild(fragment);
    this.open();
  }
  clear() {
    if (this.dropdown) this.dropdown.innerHTML = "";
  }

  close() {
    if (this.dropdown) this.dropdown.style.display = "none";
  }
}
