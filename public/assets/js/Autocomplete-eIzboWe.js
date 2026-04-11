// assets/js/Autocomplete.js

/**
 * Autocomplete module
 *
 * Attache un dropdown à un <input> et synchronise la galerie principale.
 *
 * Dataset attendus sur l'input :
 * - data-url : endpoint pour récupérer les résultats (JSON)
 * - data-target : container où injecter les résultats du dropdown
 * - data-item-url (optionnel) : transforme chaque item en <a href="...">
 * - data-result-links : 'true' pour transformer en liens
 * - data-pagination : 'true' pour activer scroll infini
 */
export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    // -------------------
    // Propriétés
    // -------------------
    this.input = input;
    this.url = input.dataset.url;
    this.dropdown = document.querySelector(input.dataset.target);
    this.itemUrlField = input.dataset.itemUrl || null;
    this.useLinks = input.dataset.resultLinks === "true";
    this.pagination = input.dataset.pagination === "true";
    this.page = 1;
    this.loading = false;
    this.hasMore = true;

    if (!this.url || !this.dropdown) {
      console.warn("Autocomplete: config manquante sur input", input);
      return;
    }

    this.init();
  }

  init() {
    console.log("Autocomplete initialisé", this.input);

    // -------------------
    // Debounce input
    // -------------------
    let debounce = null;
    this.input.addEventListener("input", e => {
      const value = e.target.value.trim();
      this.page = 1;
      this.hasMore = true;
      if (value.length < 2) {
        this.clearDropdown();
        return;
      }

      clearTimeout(debounce);
      debounce = setTimeout(() => this.fetch(value, true), 250);
    });

    // -------------------
    // Scroll infini dans dropdown
    // -------------------
    if (this.pagination) {
      this.dropdown.addEventListener("scroll", () => {
        if (!this.hasMore || this.loading) return;
        const scrollBottom =
          this.dropdown.scrollTop + this.dropdown.clientHeight;
        if (scrollBottom >= this.dropdown.scrollHeight - 50) {
          this.page++;
          this.fetch(this.input.value, false);
        }
      });
    }

    // -------------------
    // Click outside → ferme dropdown
    // -------------------
    document.addEventListener("click", e => {
      if (!this.dropdown.contains(e.target) && e.target !== this.input) {
        this.clearDropdown();
      }
    });
  }

  async fetch(query, resetDropdown = true) {
    if (!this.hasMore) return;

    this.loading = true;

    try {
      const res = await fetch(
        `${this.url}?q=${encodeURIComponent(query)}&page=${
          this.page
        }&autocomplete=1`
      );
      const data = await res.json();

      // Vérifie que data.items existe
      if (!Array.isArray(data.items)) {
        console.warn("Autocomplete: format de résultats invalide", data);
        this.clearDropdown();
        this.loading = false;
        return;
      }

      // Si reset, vide le dropdown
      if (resetDropdown) this.dropdown.innerHTML = "";

      // Construction HTML
      const ul =
        this.dropdown.querySelector("ul") || document.createElement("ul");
      if (!ul.parentNode) this.dropdown.appendChild(ul);

      data.items.forEach(item => {
        const li = document.createElement("li");
        li.classList.add("autocomplete-item");

        if (this.useLinks && this.itemUrlField && item[this.itemUrlField]) {
          const a = document.createElement("a");
          a.href = item[this.itemUrlField];
          a.textContent = item.label;
          li.appendChild(a);
        } else {
          li.textContent = item.label;
        }

        ul.appendChild(li);
      });

      // Mise à jour gallery principale si container existe
      const gallery = document.querySelector("#vehicles-search-results");
      if (gallery && data.resultsHtml) {
        gallery.innerHTML = data.resultsHtml;
        // Mise à jour pagination si fournie
        const top = document.querySelector("[data-target='pagination-top']");
        const bottom = document.querySelector(
          "[data-target='pagination-bottom']"
        );
        if (top && data.paginationTop) top.innerHTML = data.paginationTop;
        if (bottom && data.paginationBottom)
          bottom.innerHTML = data.paginationBottom;
      }

      // Pagination
      if (data.items.length === 0) this.hasMore = false;
    } catch (e) {
      console.error("Autocomplete error:", e);
      this.clearDropdown();
    } finally {
      this.loading = false;
    }
  }

  clearDropdown() {
    this.dropdown.innerHTML = "";
  }
}
