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

    // AJOUT : déclenche le refresh principal
    const form = this.input.closest("form");
    if (form) {
      // On sauvegarde la valeur
      const currentValue = this.input.value;

      form.dispatchEvent(new Event("submit", { bubbles: true }));

      // On restaure après le refresh (léger délai)
      setTimeout(() => {
        this.input.value = currentValue;
      }, 0);
    }
  }

  async fetch(query) {
    this.requestId++;
    const current = this.requestId;

    if (this.abortController) {
      this.abortController.abort();
    }

    this.abortController = new AbortController();

    const url = `${this.url}?q=${encodeURIComponent(query)}`;
    console.log("[Autocomplete] URL appelée :", url);

    try {
      const res = await fetch(url, {
        signal: this.abortController.signal,
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      console.log("[Autocomplete] status :", res.status);

      const data = await res.json();

      console.log("[Autocomplete] data :", data);

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
    if (this.dropdown) this.dropdown.innerHTML = "";
  }

  open() {
    if (this.dropdown) this.dropdown.style.display = "block";
  }

  close() {
    if (this.dropdown) this.dropdown.style.display = "none";
  }
}
