// assets/js/Autocomplete.js

/**
 * Module Autocomplete
 *
 * Prend un <input> et renvoie les résultats dans un conteneur cible.
 * Optionnellement, transforme les résultats en liens si le dataset le précise.
 *
 * Dataset attendus sur l'input :
 * - data-url : URL du endpoint autocomplete
 * - data-target : selector du conteneur où injecter les résultats
 * - data-item-url (optionnel) : si présent, chaque résultat sera transformé en <a href="...">
 */
export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url;
    this.target = document.querySelector(input.dataset.target);
    this.itemUrlField = input.dataset.itemUrl || null;

    if (!this.url || !this.target) {
      console.warn("Autocomplete: config manquante sur input", input);
      return;
    }

    this.init();
  }

  init() {
    console.log("Autocomplete recherche véhicules initialisé");
    let debounce = null;

    this.input.addEventListener("input", e => {
      const value = e.target.value.trim();

      clearTimeout(debounce);

      // Si moins de 2 caractères, vide le conteneur
      if (value.length < 2) {
        this.target.innerHTML = "";
        return;
      }

      debounce = setTimeout(() => this.fetch(value), 250);
    });
  }

  async fetch(query) {
    try {
      const res = await fetch(`${this.url}?q=${encodeURIComponent(query)}`);
      const data = await res.json();

      // data.results doit être un tableau [{label, url?}, ...]
      if (!Array.isArray(data.results)) {
        console.warn("Autocomplete: format de résultats invalide", data);
        this.target.innerHTML = "";
        return;
      }

      // Construction HTML
      let html = "";

      data.results.forEach(item => {
        if (this.itemUrlField && item[this.itemUrlField]) {
          // Transformation en lien si itemUrlField précisé
          html += `<a href="${
            item[this.itemUrlField]
          }" class="autocomplete-item">${item.label}</a>`;
        } else {
          // Sinon juste un div classique
          html += `<div class="autocomplete-item">${item.label}</div>`;
        }
      });

      this.target.innerHTML = html;
    } catch (e) {
      console.error("Autocomplete error:", e);
      this.target.innerHTML = "";
    }
  }
}
