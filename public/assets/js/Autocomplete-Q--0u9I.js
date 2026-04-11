// assets/js/Autocomplete.js

/**
 * Module Autocomplete
 *
 * Gère un input texte avec suggestions en temps réel.
 * Transforme les résultats en liens si le dataset le précise.
 * Rafraîchit la galerie de cards en même temps.
 * Gère le scroll infini pour le dropdown.
 *
 * Dataset attendus sur l'input :
 * - data-url : endpoint AJAX
 * - data-target : conteneur du dropdown
 * - data-results-container : conteneur de la galerie de cards à rafraîchir
 * - data-item-url (optionnel) : clé dans l'objet item pour créer les liens
 */
export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url;
    this.dropdownTarget = document.querySelector(input.dataset.target);
    this.resultsContainer = document.querySelector(
      input.dataset.resultsContainer
    );
    this.itemUrlField = input.dataset.itemUrl || "url";
    this.currentPage = 1;
    this.hasMore = true;

    if (!this.url || !this.dropdownTarget) {
      console.warn("Autocomplete: configuration manquante", input);
      return;
    }

    this.init();
  }

  init() {
    console.log("Autocomplete initialisé");

    let debounce = null;

    // --- Gestion saisie ---
    this.input.addEventListener("input", e => {
      const value = e.target.value.trim();
      clearTimeout(debounce);

      if (value.length < 2) {
        this.dropdownTarget.innerHTML = "";
        if (this.resultsContainer) this.resultsContainer.innerHTML = "";
        this.currentPage = 1;
        this.hasMore = true;
        return;
      }

      debounce = setTimeout(() => {
        this.currentPage = 1;
        this.hasMore = true;
        this.fetch(value, this.currentPage, true);
      }, 250);
    });

    // --- Scroll infini pour le dropdown ---
    this.dropdownTarget.addEventListener("scroll", () => {
      if (!this.hasMore) return;
      const { scrollTop, scrollHeight, clientHeight } = this.dropdownTarget;
      if (scrollTop + clientHeight >= scrollHeight - 10) {
        this.currentPage++;
        this.fetch(this.input.value.trim(), this.currentPage, false);
      }
    });
  }

  async fetch(query, page = 1, reset = false) {
    if (!query || query.length < 2) return;

    try {
      const res = await fetch(
        `${this.url}?q=${encodeURIComponent(
          query
        )}&autocomplete=true&page=${page}`
      );
      const data = await res.json();

      // --- Dropdown ---
      if (Array.isArray(data.items)) {
        let ul;
        if (reset) ul = "<ul class='autocomplete-list'>";
        // nouveau ul
        else
          ul =
            this.dropdownTarget.querySelector("ul")?.outerHTML ||
            "<ul class='autocomplete-list'>";

        data.items.forEach(item => {
          const li = `<li>${
            item[this.itemUrlField]
              ? `<a href="${
                  item[this.itemUrlField]
                }" class="autocomplete-item">${item.label}</a>`
              : `<div class="autocomplete-item">${item.label}</div>`
          }</li>`;
          if (reset) ul += li;
          else
            this.dropdownTarget
              .querySelector("ul")
              .insertAdjacentHTML("beforeend", li);
        });

        if (reset) ul += "</ul>";
        if (reset) this.dropdownTarget.innerHTML = ul;
      } else {
        this.dropdownTarget.innerHTML = "";
      }

      // --- Galerie de cards ---
      if (this.resultsContainer && data.results) {
        // Remplacement complet des cards
        this.resultsContainer.innerHTML = data.results;

        // Pagination haut
        const paginationTopTarget = this.resultsContainer.dataset.paginationTop
          ? document.querySelector(this.resultsContainer.dataset.paginationTop)
          : null;
        if (paginationTopTarget && data.paginationTop)
          paginationTopTarget.innerHTML = data.paginationTop;

        // Pagination bas
        const paginationBottomTarget = this.resultsContainer.dataset
          .paginationBottom
          ? document.querySelector(
              this.resultsContainer.dataset.paginationBottom
            )
          : null;
        if (paginationBottomTarget && data.paginationBottom)
          paginationBottomTarget.innerHTML = data.paginationBottom;
      }

      // --- Plus de résultats pour scroll ---
      if (!data.items || data.items.length === 0) this.hasMore = false;
    } catch (e) {
      console.error("Autocomplete error:", e);
      this.dropdownTarget.innerHTML = "";
    }
  }
}
