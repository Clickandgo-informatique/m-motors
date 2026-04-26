/**
 * Debounce pour limiter les appels API
 */
function debounce(fn, delay = 300) {
  let timer;

  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(null, args), delay);
  };
}

/**
 * Sécurise le texte contre injection HTML
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

export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url;
    this.itemUrlTemplate = input.dataset.itemUrl || "";
    this.hiddenSelector = input.dataset.hiddenTarget || "";

    this.page = 1;
    this.loading = false;
    this.dropdown = null;

    // contrôle requêtes AJAX
    this.abortController = null;

    if (!this.url) {
      console.error("Autocomplete: URL manquante");
      return;
    }

    this.initDropdown();
    this.attachEvents();
  }

  /**
   * Création dropdown
   */
  initDropdown() {
    this.dropdown = document.createElement("div");
    this.dropdown.className = "dropdown-results";

    this.dropdown.style.position = "absolute";
    this.dropdown.style.zIndex = "1000";
    this.dropdown.style.display = "none";

    if (this.input.parentNode) {
      const style = getComputedStyle(this.input.parentNode);
      if (style.position === "static") {
        this.input.parentNode.style.position = "relative";
      }

      this.input.parentNode.appendChild(this.dropdown);
    }
  }

  /**
   * Events input + clic extérieur
   */
  attachEvents() {
    this.input.addEventListener(
      "input",
      debounce(() => {
        const value = this.input.value.trim();
        this.page = 1;

        if (value.length < 2) {
          this.clear();
          this.close();
          return;
        }

        this.fetch();
      }, 300)
    );

    document.addEventListener("click", e => {
      if (!this.dropdown.contains(e.target) && e.target !== this.input) {
        this.close();
      }
    });
  }

  /**
   * Requête AJAX sécurisée
   */
  async fetch() {
    this.loading = true;

    // annule requête précédente
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

      // IMPORTANT : on lit en texte pour éviter crash JSON
      const text = await res.text();

      let data;

      try {
        data = JSON.parse(text);
      } catch (e) {
        console.error("Réponse non JSON :", text);
        this.clear();
        this.close();
        return;
      }

      if (!data || !Array.isArray(data.items)) {
        console.error("Format API invalide :", data);
        this.clear();
        this.close();
        return;
      }

      this.render(data.items);
    } catch (err) {
      if (err.name !== "AbortError") {
        console.error("Autocomplete error:", err);
      }
    } finally {
      this.loading = false;
    }
  }

  /**
   * Affichage des résultats
   */
  render(items) {
    if (this.dropdown.style.display === "none") return;

    this.clear();

    if (items.length === 0) {
      this.dropdown.innerHTML =
        "<div class='dropdown-no-results'>Aucun résultat</div>";
      this.open();
      return;
    }

    const fragment = document.createDocumentFragment();

    items.forEach(item => {
      const el = document.createElement("a");

      el.className = "dropdown-item";
      el.href = this.itemUrlTemplate.replace("ID_PLACEHOLDER", item.id);

      el.textContent = item.label;

      el.addEventListener("click", e => {
        e.preventDefault();

        this.select(item);

        // navigation vers fiche véhicule
        window.location.href = el.href;
      });

      fragment.appendChild(el);
    });

    this.dropdown.appendChild(fragment);
    this.open();
  }

  /**
   * Sélection d’un item
   */
  select(item) {
    this.input.value = item.label;

    if (this.hiddenSelector) {
      const hidden = document.querySelector(this.hiddenSelector);
      if (hidden) hidden.value = item.id;
    }

    // stop requêtes en cours
    if (this.abortController) {
      this.abortController.abort();
    }

    this.close();
  }

  /**
   * UI helpers
   */
  clear() {
    this.dropdown.innerHTML = "";
  }

  open() {
    this.dropdown.style.display = "block";
  }

  close() {
    this.dropdown.style.display = "none";
  }
}
