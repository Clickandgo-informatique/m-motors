// assets/js/Autocomplete.js

/**
 * Module Autocomplete
 *
 * Prend un <input> et renvoie les résultats dans un conteneur cible.
 * Optionnellement, transforme les résultats en liens si le dataset le précise.
 * Rafraîchit également le container principal des cartes si nécessaire.
 *
 * Dataset attendus sur l'input :
 * - data-url : URL du endpoint autocomplete
 * - data-target : selector du conteneur où injecter les résultats
 * - data-item-url (optionnel) : si présent, chaque résultat sera transformé en <a href="...">
 * - data-results-container (optionnel) : container à mettre à jour avec la vue des cards
 */
export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url; // Endpoint AJAX
    this.target = document.querySelector(input.dataset.target); // Conteneur dropdown
    this.itemUrlField = input.dataset.itemUrl || null; // Champ URL pour transformer en <a>
    this.resultsContainerSelector = input.dataset.resultsContainer || null; // Container principal des cards
    this.resultsContainer = this.resultsContainerSelector
      ? document.querySelector(this.resultsContainerSelector)
      : null;

    if (!this.url || !this.target) {
      console.warn("Autocomplete: config manquante sur input", input);
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

      // Si moins de 2 caractères, vide le conteneur
      if (value.length < 2) {
        this.target.innerHTML = "";
        return;
      }

      // Déclenche fetch après un délai (debounce)
      debounce = setTimeout(() => this.fetch(value), 250);
    });
  }

  async fetch(query) {
    try {
      const res = await fetch(`${this.url}?q=${encodeURIComponent(query)}`);
      const data = await res.json();

      if (!data.items || !Array.isArray(data.items)) {
        console.warn("Autocomplete: format de résultats invalide", data);
        this.target.innerHTML = "";
        return;
      }

      // -------------------------------
      // Construction HTML pour dropdown
      // -------------------------------
      let html = "<ul class='autocomplete-list'>";

      data.items.forEach(item => {
        if (this.itemUrlField && item[this.itemUrlField]) {
          html += `<li class="autocomplete-item"><a href="${
            item[this.itemUrlField]
          }">${item.label}</a></li>`;
        } else {
          html += `<li class="autocomplete-item">${item.label}</li>`;
        }
      });

      html += "</ul>";

      this.target.innerHTML = html;

      // -------------------------------
      // Rafraîchissement container des cards
      // -------------------------------
      if (this.resultsContainer && data.results) {
        this.resultsContainer.innerHTML = data.results;
      }
    } catch (e) {
      console.error("Autocomplete error:", e);
      this.target.innerHTML = "";
      if (this.resultsContainer) this.resultsContainer.innerHTML = "";
    }
  }
}
