// assets/js/Autocomplete.js

/**
 * Module Autocomplete
 *
 * Fonctionnalités :
 * - Transforme les résultats en liens si `data-item-url` est présent
 * - Met à jour le conteneur principal (grid/table) en même temps
 *
 * Dataset attendus sur l'input :
 * - data-url : URL du endpoint autocomplete
 * - data-target : selector du conteneur où injecter les résultats (dropdown autocomplete)
 * - data-item-url (optionnel) : si présent, chaque résultat sera transformé en <a href="...">
 * - data-result-div : container principal de la page (grid/table) à mettre à jour
 */
export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url;
    this.target = document.querySelector(input.dataset.target); // dropdown autocomplete
    this.itemUrlField = input.dataset.itemUrl || null; // propriété du résultat à transformer en lien
    this.mainContainer = document.querySelector(input.dataset.resultDiv); // container grid/table

    if (!this.url || !this.target) {
      console.warn("Autocomplete: config manquante sur input", input);
      return;
    }

    this.init();
  }

  init() {
    console.log("Autocomplete initialisé pour input", this.input);

    let debounce = null;

    this.input.addEventListener("input", e => {
      const value = e.target.value.trim();

      clearTimeout(debounce);

      if (value.length < 2) {
        this.target.innerHTML = "";
        return;
      }

      debounce = setTimeout(() => this.fetch(value), 250);
    });
  }

  async fetch(query) {
    try {
      const res = await fetch(`${this.url}?q=${encodeURIComponent(query)}&autocomplete=1`);
      const data = await res.json();

      // Vérifie que le JSON contient soit 'items' (autocomplete) soit 'results' (HTML pour la page)
      if (!data.items && !data.results) {
        console.warn("Autocomplete: format de résultats invalide", data);
        this.target.innerHTML = "";
        return;
      }

      // --- Liste de suggestions autocomplete ---
      if (Array.isArray(data.items)) {
        let html = "";
        data.items.forEach(item => {
          if (this.itemUrlField && item[this.itemUrlField]) {
            html += `<a href="${item[this.itemUrlField]}" class="autocomplete-item">${item.label}</a>`;
          } else {
            html += `<div class="autocomplete-item">${item.label}</div>`;
          }
        });
        this.target.innerHTML = html;
      }

      // --- Mise à jour du container principal grid/table ---
      if (data.results && this.mainContainer) {
        this.mainContainer.innerHTML = data.results;

        // Ré-applique les boutons favoris dans le nouveau contenu
        document.querySelectorAll('[data-action="toggle-favorite"]').forEach(button => {
          if (!button.dataset.favoriteInitialized) {
            new (await import("./ToggleVehicleFavorite.js")).default(button);
            button.dataset.favoriteInitialized = "true";
          }
        });
      }

    } catch (e) {
      console.error("Autocomplete error:", e);
      this.target.innerHTML = "";
    }
  }
}