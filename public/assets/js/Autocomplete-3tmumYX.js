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
export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url; // URL AJAX
    this.containerId = input.dataset.target || null; // container des résultats
    this.itemUrlTemplate = input.dataset.itemUrl || ""; // /edit/ID_PLACEHOLDER
    this.useLinks = input.dataset.resultLinks === "true"; // true pour transformer en <a>
    this.paginate = input.dataset.pagination === "true"; // scroll infini
    this.highlight = input.dataset.highlight === "true"; // surbrillance texte

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

  /**
   * Crée le dropdown si nécessaire
   */
  initDropdown() {
    if (this.containerId) {
      this.dropdown = document.getElementById(this.containerId);
    }
    if (!this.dropdown) {
      this.dropdown = document.createElement("div");
      this.dropdown.className = "dropdown-results";
      this.input.parentNode.appendChild(this.dropdown);
    }
    this.dropdown.style.position = "absolute";
    this.dropdown.style.zIndex = "1000";
    this.dropdown.style.display = "none";
  }

  /**
   * Attache les événements sur l'input
   */
  attachEvents() {
    this.input.addEventListener("input", () => {
      this.page = 1;
      this.dropdown.innerHTML = "";
      if (this.input.value.trim() !== "") this.fetchResults();
      else this.closeDropdown();
    });

    // scroll pour pagination
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

    // clic à l'extérieur pour fermer
    document.addEventListener("click", e => {
      if (!this.dropdown.contains(e.target) && e.target !== this.input) {
        this.closeDropdown();
      }
    });
  }

  /**
   * Requête AJAX pour récupérer les résultats
   * @param append bool true si ajout au dropdown (scroll)
   */
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
        this.loading = false;
        this.openDropdown();
        return;
      }

      const fragment = document.createDocumentFragment();

      data.items.forEach(item => {
        let el;
        if (this.useLinks) {
          el = document.createElement("a");
          el.href = this.itemUrlTemplate.replace("ID_PLACEHOLDER", item.id);
          el.className = this.input.dataset.linkClass || "dropdown-link";
          el.textContent = item.label;
        } else {
          el = document.createElement("div");
          el.className = this.input.dataset.itemClass || "dropdown-item";
          el.textContent = item.label;
        }

        // surbrillance si nécessaire
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

  /**
   * Affiche le dropdown
   */
  openDropdown() {
    if (this.dropdown) this.dropdown.style.display = "block";
  }

  /**
   * Ferme le dropdown
   */
  closeDropdown() {
    if (this.dropdown) this.dropdown.style.display = "none";
  }
}
