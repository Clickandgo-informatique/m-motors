/**
 * Module Autocomplete
 *
 * Prend un <input> et renvoie les résultats dans un conteneur cible.
 * Optionnellement, transforme les résultats en <a> si data-item-url est présent.
 * Gère également la mise à jour du container principal (galerie ou table) si dataset.resultDiv correspond.
 *
 * Dataset attendus sur l'input :
 * - data-url : URL du endpoint autocomplete
 * - data-target : selector du conteneur où injecter les résultats
 * - data-item-url (optionnel) : si présent, chaque résultat sera transformé en <a href="...">
 * - data-result-div (optionnel) : container principal à mettre à jour
 */
export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url;
    this.target = document.querySelector(
      input.dataset.target || "#autocomplete-results"
    );
    this.itemUrlField = input.dataset.itemUrl || null;
    this.mainContainer = document.querySelector(
      input.dataset.resultDiv || null
    );

    if (!this.url || !this.target) {
      console.warn("Autocomplete: config manquante sur input", input);
      return;
    }

    this.init();
  }

  /**
   * Initialisation du listener sur input
   */
  init() {
    console.log("Autocomplete initialisé");

    let debounce = null;

    this.input.addEventListener("input", e => {
      const value = e.target.value.trim();

      clearTimeout(debounce);

      // Moins de 2 caractères → vide le dropdown
      if (value.length < 2) {
        this.target.innerHTML = "";
        return;
      }

      // Debounce pour éviter trop de requêtes
      debounce = setTimeout(() => this.fetch(value), 250);
    });
  }

  /**
   * Requête AJAX pour récupérer les résultats
   */
  async fetch(query) {
    try {
      const res = await fetch(
        `${this.url}?q=${encodeURIComponent(query)}&autocomplete=1`
      );
      const data = await res.json();

      // Vérifie que le format est correct
      if (!data.items) {
        console.warn("Autocomplete: format de résultats invalide", data);
        this.target.innerHTML = "";
        return;
      }

      // --- Construction du dropdown en <ul><li> ---
      let html = '<ul class="autocomplete-list">';
      data.items.forEach(item => {
        if (this.itemUrlField && item[this.itemUrlField]) {
          html += `<li><a href="${
            item[this.itemUrlField]
          }" class="autocomplete-item">${item.label}</a></li>`;
        } else {
          html += `<li class="autocomplete-item">${item.label}</li>`;
        }
      });
      html += "</ul>";

      this.target.innerHTML = html;

      // --- Mise à jour du container principal si présent ---
      if (this.mainContainer && data.results) {
        this.mainContainer.innerHTML = data.results;
      }
    } catch (e) {
      console.error("Autocomplete error:", e);
      this.target.innerHTML = "";
    }
  }
}
