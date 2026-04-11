// assets/js/Autocomplete.js

/**
 * Module Autocomplete
 *
 * Transforme un <input> en champ de recherche avec suggestions AJAX.
 * Optionnellement, chaque résultat peut devenir un lien si `data-item-url` est défini.
 *
 * Dataset attendus sur l'input :
 * - data-autocomplete="true"          → pour activer le module
 * - data-fetch-url="/endpoint"        → URL du backend pour l'autocomplete
 * - data-result-div="id"              → conteneur où injecter les résultats
 * - data-item-url="fieldname" (optionnel) → si présent, chaque résultat sera un <a href="...">
 */
export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    // -----------------------------
    // Récupération des dataset
    // -----------------------------
    this.input = input;
    this.url = input.dataset.fetchUrl; // endpoint AJAX
    this.target = document.getElementById(input.dataset.resultDiv);
    this.itemUrlField = input.dataset.itemUrl || null;

    if (!this.url || !this.target) {
      console.warn("Autocomplete: config manquante sur input", input);
      return;
    }

    // -----------------------------
    // Initialisation
    // -----------------------------
    this.init();
  }

  init() {
    console.log("Autocomplete recherche véhicules initialisé");

    let debounce = null;

    // Écoute sur la saisie
    this.input.addEventListener("input", e => {
      const value = e.target.value.trim();

      clearTimeout(debounce);

      // Si moins de 2 caractères, vide le conteneur
      if (value.length < 2) {
        this.target.innerHTML = "";
        return;
      }

      // Débounce 250ms avant de lancer la requête
      debounce = setTimeout(() => this.fetch(value), 250);
    });
  }

  // -----------------------------
  // Requête AJAX
  // -----------------------------
  async fetch(query) {
    try {
      const res = await fetch(`${this.url}?q=${encodeURIComponent(query)}`);
      const data = await res.json();

      // Vérifie que data.results est un tableau
      if (!Array.isArray(data.results)) {
        console.warn("Autocomplete: format de résultats invalide", data);
        this.target.innerHTML = "";
        return;
      }

      // -----------------------------
      // Construction HTML des résultats
      // -----------------------------
      let html = "";

      data.results.forEach(item => {
        // Si itemUrlField présent et existe dans l'objet, créer un lien
        if (this.itemUrlField && item[this.itemUrlField]) {
          html += `<a href="${
            item[this.itemUrlField]
          }" class="autocomplete-item">${item.label}</a>`;
        } else {
          // Sinon div simple
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
