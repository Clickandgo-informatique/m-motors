// assets/js/Autocomplete.js

/**
 * Module Autocomplete
 *
 * Prend un <input> et renvoie les résultats dans un conteneur cible.
 * Transforme les résultats en <ul><li><a></a></li></ul> si URL précisé.
 * Met à jour en même temps le container des cards (grid / table).
 *
 * Dataset attendus sur l'input :
 * - data-url : endpoint autocomplete
 * - data-target : selector du conteneur dropdown
 * - data-item-url (optionnel) : si présent, chaque item devient un <a>
 * - data-results-container : selector du container des cards à rafraîchir
 */
export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url;
    this.dropdownTarget = document.querySelector(input.dataset.target);
    this.resultsContainer = input.dataset.resultsContainer
      ? document.querySelector(input.dataset.resultsContainer)
      : null;
    this.itemUrlField = input.dataset.itemUrl || "url";

    if (!this.url || !this.dropdownTarget) {
      console.warn("Autocomplete: configuration manquante", input);
      return;
    }

    this.init();
  }

  init() {
    console.log("Autocomplete initialisé");

    let debounce = null;

    this.input.addEventListener("input", e => {
      const value = e.target.value.trim();

      clearTimeout(debounce);

      // Si moins de 2 caractères, vide le dropdown
      if (value.length < 2) {
        this.dropdownTarget.innerHTML = "";
        return;
      }

      debounce = setTimeout(() => this.fetch(value), 250);
    });
  }

  async fetch(query) {
    try {
      const res = await fetch(
        `${this.url}?q=${encodeURIComponent(query)}&autocomplete=true`
      );
      const data = await res.json();

      // --- Construction du dropdown ---
      if (Array.isArray(data.items)) {
        let html = "<ul class='autocomplete-list'>";
        data.items.forEach(item => {
          if (item.url) {
            html += `<li><a href="${item.url}" class="autocomplete-item">${item.label}</a></li>`;
          } else {
            html += `<li><div class="autocomplete-item">${item.label}</div></li>`;
          }
        });
        html += "</ul>";
        this.dropdownTarget.innerHTML = html;
      } else {
        console.warn("Autocomplete: format de résultats invalide", data);
        this.dropdownTarget.innerHTML = "";
      }

      // --- Mise à jour des cards si container défini ---
      if (this.resultsContainer && data.results) {
        this.resultsContainer.innerHTML = data.results;

        // Pagination en haut
        const paginationTopTarget = this.resultsContainer.dataset.paginationTop
          ? document.querySelector(this.resultsContainer.dataset.paginationTop)
          : null;
        if (paginationTopTarget && data.paginationTop) {
          paginationTopTarget.innerHTML = data.paginationTop;
        }

        // Pagination en bas
        const paginationBottomTarget = this.resultsContainer.dataset
          .paginationBottom
          ? document.querySelector(
              this.resultsContainer.dataset.paginationBottom
            )
          : null;
        if (paginationBottomTarget && data.paginationBottom) {
          paginationBottomTarget.innerHTML = data.paginationBottom;
        }
      }
    } catch (e) {
      console.error("Autocomplete error:", e);
      this.dropdownTarget.innerHTML = "";
    }
  }
}
