// assets/js/Autocomplete.js

/**
 * Module Autocomplete
 *
 * Prend un <input> et renvoie les résultats dans un conteneur cible.
 * Optionnellement, transforme les résultats en <a href="..."> si dataset.itemUrl est défini.
 * Met à jour en même temps le conteneur principal (ex: liste de cards) si le backend renvoie HTML.
 *
 * Dataset attendus sur l'input :
 * - data-url : URL du endpoint autocomplete
 * - data-result-div : selector du conteneur dropdown pour les suggestions
 * - data-target : selector du conteneur principal à mettre à jour (cards / table)
 * - data-item-url (optionnel) : si présent, chaque résultat sera transformé en <a href="...">
 * - data-highlight (optionnel) : si true, surligne la partie correspondante du label
 */
export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url;
    this.dropdown = document.querySelector(input.dataset.resultDiv);
    this.mainTarget = document.querySelector(
      input.dataset.target || "#vehicles-results"
    );
    this.itemUrlField = input.dataset.itemUrl || null;
    this.highlight = input.dataset.highlight === "true";

    if (!this.url || !this.dropdown) {
      console.warn("Autocomplete: configuration manquante sur input", input);
      return;
    }

    this.init();
  }

  init() {
    console.log("Autocomplete initialisé pour", this.input);

    let debounce = null;

    // Événement input
    this.input.addEventListener("input", e => {
      const value = e.target.value.trim();

      clearTimeout(debounce);

      // Si moins de 2 caractères, vide le dropdown
      if (value.length < 2) {
        this.dropdown.innerHTML = "";
        return;
      }

      debounce = setTimeout(() => this.fetch(value), 250);
    });
  }

  async fetch(query) {
    try {
      // Appel AJAX vers Symfony
      const res = await fetch(
        `${this.url}?q=${encodeURIComponent(query)}&autocomplete=1`
      );
      const data = await res.json();

      // Vérifie que le tableau results existe
      if (!Array.isArray(data.results)) {
        console.warn("Autocomplete: format de résultats invalide", data);
        this.dropdown.innerHTML = "";
        return;
      }

      // Construction du dropdown
      let html = "";
      data.results.forEach(item => {
        let label = item.label;

        // Surlignage si demandé
        if (this.highlight) {
          const regex = new RegExp(
            `(${query.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")})`,
            "gi"
          );
          label = label.replace(regex, "<strong>$1</strong>");
        }

        if (this.itemUrlField && item[this.itemUrlField]) {
          html += `<a href="${
            item[this.itemUrlField]
          }" class="autocomplete-item">${label}</a>`;
        } else {
          html += `<div class="autocomplete-item">${label}</div>`;
        }
      });

      this.dropdown.innerHTML = html;

      // Mise à jour du conteneur principal si backend renvoie HTML
      if (data.html && this.mainTarget) {
        this.mainTarget.innerHTML = data.html;

        // Mettre à jour la pagination si fournie
        if (data.paginationTop) {
          const top = document.querySelector('[data-target="pagination-top"]');
          if (top) top.innerHTML = data.paginationTop;
        }
        if (data.paginationBottom) {
          const bottom = document.querySelector(
            '[data-target="pagination-bottom"]'
          );
          if (bottom) bottom.innerHTML = data.paginationBottom;
        }
      }
    } catch (e) {
      console.error("Autocomplete error:", e);
      this.dropdown.innerHTML = "";
    }
  }
}

/**
 * Initialisation globale de tous les inputs avec data-autocomplete
 */
export function initAutocomplete() {
  document.querySelectorAll('[data-autocomplete="true"]').forEach(input => {
    if (!input.dataset.autocompleteInitialized) {
      new Autocomplete(input);
      input.dataset.autocompleteInitialized = "true";
    }
  });
}

// Init immédiat au chargement du DOM
document.addEventListener("DOMContentLoaded", initAutocomplete);
