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
    // Vérification de type
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url; // endpoint autocomplete
    this.target = document.querySelector(input.dataset.target); // dropdown pour autocomplete
    this.itemUrlField = input.dataset.itemUrl || null; // optionnel
    this.mainContainer = document.querySelector(input.dataset.resultDiv); // container principal (cards ou table)

    // Vérification de la configuration
    if (!this.url || !this.target) {
      console.warn("Autocomplete: configuration manquante sur input", input);
      return;
    }

    // Initialisation
    this.init();
  }

  /**
   * Initialisation : écoute des événements input
   */
  init() {
    console.log("Autocomplete initialisé pour input", this.input);

    let debounce = null;

    this.input.addEventListener("input", e => {
      const value = e.target.value.trim();

      // Clear debounce pour éviter les requêtes rapides successives
      clearTimeout(debounce);

      // Si moins de 2 caractères, on vide le container
      if (value.length < 2) {
        this.target.innerHTML = "";
        return;
      }

      // Déclenche la requête après 250ms
      debounce = setTimeout(() => this.fetch(value), 250);
    });
  }

  /**
   * Requête AJAX vers le backend et mise à jour des containers
   */
  async fetch(query) {
    try {
      // Requête GET vers l'endpoint avec le paramètre 'autocomplete'
      const res = await fetch(
        `${this.url}?q=${encodeURIComponent(query)}&autocomplete=1`
      );
      const data = await res.json();

      // --- Mise à jour du dropdown autocomplete ---
      if (Array.isArray(data.items)) {
        let html = "";

        data.items.forEach(item => {
          // Si itemUrlField précisé et présent, transforme en <a>
          if (this.itemUrlField && item[this.itemUrlField]) {
            html += `<a href="${
              item[this.itemUrlField]
            }" class="autocomplete-item">${item.label}</a>`;
          } else {
            html += `<div class="autocomplete-item">${item.label}</div>`;
          }
        });

        this.target.innerHTML = html;
      }

      // --- Mise à jour du container principal (grid/table) ---
      if (data.results && this.mainContainer) {
        this.mainContainer.innerHTML = data.results;

        // Réinitialisation des boutons favoris dans le nouveau contenu
        document
          .querySelectorAll('[data-action="toggle-favorite"]')
          .forEach(button => {
            if (!button.dataset.favoriteInitialized) {
              new ToggleVehicleFavorite(button);
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
