function debounce(fn, delay = 250) {
  let timer;

  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url;

    this.linkMode = input.dataset.linkMode === "1";
    this.baseUrl = input.dataset.baseUrl || "";

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

    const handler = debounce(() => this.onInput(), 250);

    this.input.addEventListener("input", handler);

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

    const url = `${this.url}?q=${encodeURIComponent(query)}`;

    try {
      const res = await fetch(url, {
        signal: this.abortController.signal,
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
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
    if (!this.dropdown) return;

    this.clear();

    if (!Array.isArray(items)) {
      console.error("[Autocomplete] items invalid", items);
      return;
    }

    if (!items.length) {
      this.dropdown.innerHTML =
        "<div class='dropdown-no-results'>Aucun résultat</div>";
      this.open();
      return;
    }

    const fragment = document.createDocumentFragment();

    items.forEach(item => {
      const el = document.createElement("div");
      el.className = "dropdown-item";

      if (this.linkMode) {
        const a = document.createElement("a");

        const url = `${this.baseUrl}${item.id}/edit`;

        a.textContent = item.label;
        a.href = url;
        a.setAttribute("data-ajax-modal", url);

        el.appendChild(a);

        a.addEventListener("click", e => {
          e.preventDefault();
          window.location.href = url;
        });
      } else {
        el.textContent = item.label;

        el.addEventListener("click", () => {
          this.select(item.label);
        });
      }

      fragment.appendChild(el);
    });

    this.dropdown.appendChild(fragment);
    this.open();
  }

  select(item) {
    const form = this.input.closest("form");

    this.input.value = item.label ?? "";

    const hidden = form.querySelector("[data-autocomplete-hidden]");
    if (hidden) hidden.value = item.id;

    // IMPORTANT: empêche le debounce input de déclencher send()
    this.ready = false;

    setTimeout(() => {
      this.ready = true;
    }, 300);

    if (!this.linkMode) {
      form.dispatchEvent(new Event("submit", { bubbles: true }));
    }

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
