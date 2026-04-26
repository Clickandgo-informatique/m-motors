// assets/js/Autocomplete.js

/**
 * Simple utilitaire de debounce
 * Permet de limiter le nombre d'appels lors de la frappe
 */
function debounce(fn, delay = 300) {
  let timer;

  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(null, args), delay);
  };
}

/**
 * Escape HTML pour éviter les injections
 */
function escapeHtml(str) {
  return str.replace(
    /[&<>"']/g,
    m =>
      ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"
      }[m])
  );
}

/**
 * Highlight sécurisé du texte
 */
function highlight(text, query) {
  if (!query) return escapeHtml(text);

  const safeText = escapeHtml(text);
  const regex = new RegExp(`(${query})`, "gi");

  return safeText.replace(regex, "<mark>$1</mark>");
}

export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url;
    this.containerId = (input.dataset.target || "").replace(/^#/, "");
    this.itemUrlTemplate = input.dataset.itemUrl || "";
    this.useLinks = input.dataset.resultLinks === "true";
    this.paginate = input.dataset.pagination === "true";
    this.highlightEnabled = input.dataset.highlight === "true";
    this.hiddenSelector = input.dataset.hiddenTarget || "";

    if (!this.url) {
      console.error("Autocomplete : URL manquante");
      return;
    }

    this.page = 1;
    this.loading = false;
    this.dropdown = null;
    this.abortController = null;

    this.initDropdown();
    this.attachEvents();
  }

  /**
   * Initialisation du conteneur dropdown
   */
  initDropdown() {
    if (this.containerId) {
      this.dropdown = document.getElementById(this.containerId);
    }

    if (!this.dropdown) {
      this.dropdown = document.createElement("div");
      this.dropdown.className = "dropdown-results";

      if (this.input.parentNode) {
        this.input.parentNode.appendChild(this.dropdown);
      }
    }

    this.dropdown.style.position = "absolute";
    this.dropdown.style.zIndex = "1000";
    this.dropdown.style.display = "none";

    const parentStyle = getComputedStyle(this.input.parentNode);
    if (parentStyle.position === "static") {
      this.input.parentNode.style.position = "relative";
    }
  }

  /**
   * Bind events (avec debounce sur input)
   */
  attachEvents() {
    this.input.addEventListener(
      "input",
      debounce(() => {
        this.page = 1;

        if (this.input.value.trim() === "") {
          this.clearDropdown();
          this.closeDropdown();
          return;
        }

        this.fetchResults();
      }, 300)
    );

    // pagination scroll
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

    // fermeture au clic extérieur
    document.addEventListener("click", e => {
      if (!this.dropdown.contains(e.target) && e.target !== this.input) {
        this.closeDropdown();
      }
    });
  }

  /**
   * Récupération AJAX
   */
  async fetchResults(append = false) {
    this.loading = true;

    // annule requête précédente si encore active
    if (this.abortController) {
      this.abortController.abort();
    }

    this.abortController = new AbortController();

    try {
      const res = await fetch(
        `${this.url}?autocomplete=1&q=${encodeURIComponent(
          this.input.value
        )}&page=${this.page}`,
        { signal: this.abortController.signal }
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

        const label = this.highlightEnabled
          ? highlight(item.label, this.input.value)
          : escapeHtml(item.label);

        if (this.useLinks) {
          el = document.createElement("a");
          el.href = this.itemUrlTemplate.replace("ID_PLACEHOLDER", item.id);
          el.className = linkClass;
          el.innerHTML = label;

          el.addEventListener("click", e => {
            e.preventDefault();

            this.selectItem(item);

            // navigation vers fiche
            window.location.href = el.href;
          });
        } else {
          el = document.createElement("div");
          el.className = itemClass;
          el.innerHTML = label;

          el.addEventListener("click", () => {
            this.selectItem(item);
          });
        }

        fragment.appendChild(el);
      });

      if (!append) {
        this.dropdown.innerHTML = "";
      }

      this.dropdown.appendChild(fragment);
      this.openDropdown();
    } catch (err) {
      if (err.name !== "AbortError") {
        console.error("Autocomplete AJAX error:", err);
      }
    } finally {
      this.loading = false;
    }
  }

  /**
   * Sélection d'un item
   */
  selectItem(item) {
    this.input.value = item.label;

    if (this.hiddenSelector) {
      const hiddenInput = document.querySelector(this.hiddenSelector);
      if (hiddenInput) {
        hiddenInput.value = item.id;
      }
    }

    this.closeDropdown();
  }

  /**
   * Nettoyage dropdown
   */
  clearDropdown() {
    if (this.dropdown) {
      this.dropdown.innerHTML = "";
    }
  }

  openDropdown() {
    if (this.dropdown) {
      this.dropdown.style.display = "block";
    }
  }

  closeDropdown() {
    if (this.dropdown) {
      this.dropdown.style.display = "none";
    }
  }
}
