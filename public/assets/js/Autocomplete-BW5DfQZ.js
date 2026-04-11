// assets/js/Autocomplete.js

/**
 * Classe Autocomplete
 * -------------------
 * Permet d'ajouter de l'autocomplétion sur un input avec :
 * - requête AJAX vers le serveur
 * - dropdown scroll infini
 * - liens cliquables
 * - fermeture si clic en dehors
 */
// assets/js/Autocomplete.js
export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url;
    this.containerId = (input.dataset.target || "").replace(/^#/, ""); // supprime le #
    this.itemUrlTemplate = input.dataset.itemUrl || "";
    this.useLinks = input.dataset.resultLinks === "true";
    this.paginate = input.dataset.pagination === "true";
    this.highlight = input.dataset.highlight === "true";

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
    if (this.containerId) {
      this.dropdown = document.getElementById(this.containerId);
    }
    if (!this.dropdown) {
      this.dropdown = document.createElement("div");
      this.dropdown.className = "dropdown-results";
      if (this.input.parentNode)
        this.input.parentNode.appendChild(this.dropdown);
    }
    this.dropdown.style.position = "absolute";
    this.dropdown.style.zIndex = "1000";
    this.dropdown.style.display = "none";

    // parent position relative
    const parentStyle = getComputedStyle(this.input.parentNode);
    if (parentStyle.position === "static") {
      this.input.parentNode.style.position = "relative";
    }
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
      if (!this.dropdown.contains(e.target) && e.target !== this.input) {
        this.closeDropdown();
      }
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

      if (!data.items || !data.items.length) {
        if (!append)
          this.dropdown.innerHTML =
            "<div class='dropdown-no-results'>Aucun résultat</div>";
        this.openDropdown();
        return;
      }

      const fragment = document.createDocumentFragment();

      const itemClass = this.input.dataset.itemClass || "dropdown-item";
      const linkClass = this.input.dataset.linkClass || "dropdown-link";

      data.items.forEach(item => {
        let el;
        if (this.useLinks) {
          el = document.createElement("a");
          el.href = this.itemUrlTemplate.replace("ID_PLACEHOLDER", item.id);
          el.className = linkClass;
          el.textContent = item.label;
        } else {
          el = document.createElement("div");
          el.className = itemClass;
          el.textContent = item.label;
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

  openDropdown() {
    if (this.dropdown) this.dropdown.style.display = "block";
  }

  closeDropdown() {
    if (this.dropdown) this.dropdown.style.display = "none";
  }
}
