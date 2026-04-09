// assets/js/Autocomplete.js

/**
 * Module Autocomplete
 *
 * Gère un <input> avec :
 * - suggestions dropdown
 * - transformation des résultats en liens si data-item-url présent
 * - mise à jour de la grille ou table principale en même temps
 *
 * Dataset attendus sur l'input :
 * - data-url : endpoint AJAX pour l'autocomplete
 * - data-target : conteneur où injecter les résultats du dropdown
 * - data-item-url (optionnel) : si présent, chaque résultat devient un <a href="...">
 * - data-main-container (optionnel) : conteneur principal à mettre à jour (cards/table)
 */
import ToggleVehicleFavorite from "./ToggleVehicleFavorite.js";

export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url;
    this.target = document.querySelector(input.dataset.target);
    this.itemUrlField = input.dataset.itemUrl || null;
    this.mainContainer = document.querySelector(
      input.dataset.mainContainer || "#vehicles-results"
    );

    if (!this.url || !this.target) {
      console.warn("Autocomplete: config manquante sur input", input);
      return;
    }

    this.init();
  }

  init() {
    console.log("Autocomplete initialisé pour", this.input);

    let debounce = null;

    this.input.addEventListener("input", e => {
      const value = e.target.value.trim();

      clearTimeout(debounce);

      // Si moins de 2 caractères, vide le dropdown
      if (value.length < 2) {
        this.target.innerHTML = "";
        return;
      }

      // Déclenche la requête après un petit délai
      debounce = setTimeout(() => this.fetch(value), 250);
    });
  }

  async fetch(query) {
    try {
      // --- Requête AJAX ---
      const params = new URLSearchParams();
      params.set("q", query);
      params.set("autocomplete", "true");

      const res = await fetch(`${this.url}?${params.toString()}`, {
        headers: { "X-Requested-With": "XMLHttpRequest" }
      });

      const data = await res.json();

      // --- Dropdown ---
      let html = "";

      if (Array.isArray(data.items)) {
        data.items.forEach(item => {
          if (this.itemUrlField) {
            // Génération du lien avec remplacement ID_PLACEHOLDER si nécessaire
            const href = item[this.itemUrlField]
              ? item[this.itemUrlField]
              : this.input.dataset.itemUrl.replace("ID_PLACEHOLDER", item.id);
            html += `<a href="${href}" class="autocomplete-item">${item.label}</a>`;
          } else {
            html += `<div class="autocomplete-item">${item.label}</div>`;
          }
        });
      } else {
        console.warn("Autocomplete: format de résultats invalide", data);
      }

      this.target.innerHTML = html;

      // --- Mise à jour du container principal (cards/table) ---
      if (this.mainContainer && data.resultsHtml) {
        this.mainContainer.innerHTML = data.resultsHtml;

        // Réinitialisation des boutons favoris pour les nouveaux items
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
