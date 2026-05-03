/**
 * Autocomplete
 * ------------------------------------------------------------------
 * Suggestions + mode lien + modale AJAX
 * ------------------------------------------------------------------
 */

function debounce(fn, delay = 300) {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), delay);
  };
}

export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.form = input.closest("form");

    this.url = input.dataset.url;
    if (!this.url) return;

    this.isLinkMode =
      input.dataset.resultLinks === "true" ||
      this.form?.dataset.resultLinks === "true";

    this.itemUrlTemplate = this.form?.dataset.itemUrlTemplate || "";

    this.init();
  }

  init() {
    this.bindDropdown();

    this.input.addEventListener(
      "input",
      debounce(() => this.onInput(), 300)
    );
  }

  bindDropdown() {
    const wrapper = this.input.closest(".dropdown-wrapper");
    this.dropdown = wrapper?.querySelector(".dropdown-results");
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
    const res = await fetch(
      `${this.url}?mode=autocomplete&q=${encodeURIComponent(query)}`
    );

    const data = await res.json();
    this.render(data.items || []);
  }

  render(items) {
    this.clear();

    if (!items.length) {
      this.dropdown.innerHTML = "Aucun résultat";
      this.open();
      return;
    }

    const frag = document.createDocumentFragment();

    items.forEach(item => {
      const el = document.createElement(this.isLinkMode ? "a" : "div");

      el.className = "dropdown-item";
      el.textContent = item.label;

      if (this.isLinkMode) {
        const url = this.itemUrlTemplate.replace("ID_PLACEHOLDER", item.id);

        el.href = url;

        el.setAttribute("data-ajax-modal", "true");
        el.setAttribute("data-ajax-url", url);
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
