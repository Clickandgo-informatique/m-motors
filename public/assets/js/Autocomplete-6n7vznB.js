// assets/js/Autocomplete.js
export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url;
    this.containerId = (input.dataset.target || "").replace(/^#/, "");
    this.itemUrlTemplate = input.dataset.itemUrl || "";
    this.useLinks = input.dataset.resultLinks === "true";
    this.paginate = input.dataset.pagination === "true";
    this.highlight = input.dataset.highlight === "true";
    this.hiddenSelector = input.dataset.hiddenTarget || "";

    if (!this.url) {
      console.error("Autocomplete : config manquante sur input", input);
      return;
    }

    this.page = 1;
    this.loading = false;
    this.dropdown = null;

    this.initDropdown();
    this.attachEvents();
  }

  initDropdown() {
    if (this.containerId)
      this.dropdown = document.getElementById(this.containerId);
    if (!this.dropdown) {
      this.dropdown = document.createElement("div");
      this.dropdown.className = "dropdown-results";
      if (this.input.parentNode)
        this.input.parentNode.appendChild(this.dropdown);
    }
    this.dropdown.style.position = "absolute";
    this.dropdown.style.zIndex = "1000";
    this.dropdown.style.display = "none";

    const parentStyle = getComputedStyle(this.input.parentNode);
    if (parentStyle.position === "static")
      this.input.parentNode.style.position = "relative";
  }

  attachEvents() {
    this.input.addEventListener("input", () => {
      this.page = 1;
      if (this.input.value.trim() === "") {
        this.closeDropdown();
        this.dropdown.innerHTML = "";
        return;
      }
      this.fetchResults();
    });

    if (this.paginate) {
      this.dropdown.addEventListener("scroll", () => {
        if (this.loading) return;
        if (
          this.dropdown.scrollTop + this.dropdown.clientHeight >=
          this.dropdown.scrollHeight - 5
        ) {
          this.page++;
          this.fetchResults(true);
        }
      });
    }

    document.addEventListener("click", e => {
      if (!this.dropdown.contains(e.target) && e.target !== this.input)
        this.closeDropdown();
    });
  }

  async fetchResults(append = false) {
    this.loading = true;
    try {
      const res = await fetch(
        `${this.url}?autocomplete=1&q=${encodeURIComponent(
          this.input.value
        )}&page=${this.page}`
      );
      const data = await res.json();
      const items = data.items || [];

      if (!items.length) {
        this.dropdown.innerHTML =
          "<div class='dropdown-no-results'>Aucun résultat</div>";
        this.openDropdown();
        return;
      }

      const fragment = document.createDocumentFragment();
      const itemClass = this.input.dataset.itemClass || "dropdown-item";
      const linkClass = this.input.dataset.linkClass || "dropdown-link";

      items.forEach(item => {
        let el;
        if (this.useLinks) {
          el = document.createElement("a");
          el.href = this.itemUrlTemplate.replace("ID_PLACEHOLDER", item.id);
          el.className = linkClass;
          el.textContent = item.label;

          // événement clic pour remplir input + hidden
          el.addEventListener("click", e => {
            e.preventDefault();
            this.selectItem(item);
          });
        } else {
          el = document.createElement("div");
          el.className = itemClass;
          el.textContent = item.label;

          el.addEventListener("click", () => this.selectItem(item));
        }

        if (this.highlight) {
          const regex = new RegExp(`(${this.input.value})`, "gi");
          el.innerHTML = el.textContent.replace(regex, "<mark>$1</mark>");
        }

        fragment.appendChild(el);
      });

      if (append) this.dropdown.appendChild(fragment);
      else {
        this.dropdown.innerHTML = "";
        this.dropdown.appendChild(fragment);
      }

      this.openDropdown();
    } catch (err) {
      console.error("Autocomplete AJAX error:", err);
    } finally {
      this.loading = false;
    }
  }

  selectItem(item) {
    // Remplit l'input visible
    this.input.value = item.label;

    // Remplit le champ hidden
    if (this.hiddenSelector) {
      const hiddenInput = document.querySelector(this.hiddenSelector);
      if (hiddenInput) hiddenInput.value = item.id;
    }

    this.closeDropdown();
  }

  openDropdown() {
    if (this.dropdown) this.dropdown.style.display = "block";
  }

  closeDropdown() {
    if (this.dropdown) this.dropdown.style.display = "none";
  }
}
