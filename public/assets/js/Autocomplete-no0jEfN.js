// assets/js/Autocomplete.js

/**
 * Module Autocomplete
 *
 * Instancie un autocomplete sur un <input> avec datasets spécifiques.
 * Transforme les résultats en liens si dataset 'data-item-url' précisé.
 *
 * Dataset attendus sur l'input :
 * - data-autocomplete="true"
 * - data-url : URL du endpoint AJAX
 * - data-target : selector du conteneur où injecter les résultats
 * - data-item-url (optionnel) : template de lien, ex: "/vehicles/edit/ID_PLACEHOLDER"
 * - data-item-class (optionnel) : classe CSS pour chaque résultat
 * - data-link-class (optionnel) : classe CSS si résultat transformé en <a>
 * - data-no-results-class (optionnel) : classe CSS si aucun résultat
 * - data-highlight (optionnel) : "true" pour surligner le texte saisi
 */
export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    // Input et datasets
    this.input = input;
    this.url = input.dataset.url;
    this.target = document.querySelector(input.dataset.target);
    this.itemUrlTemplate = input.dataset.itemUrl || null;
    this.itemClass = input.dataset.itemClass || "autocomplete-item";
    this.linkClass = input.dataset.linkClass || "autocomplete-link";
    this.noResultsClass = input.dataset.noResultsClass || "dropdown-no-results";
    this.highlight = input.dataset.highlight === "true";

    // Vérification basique
    if (!this.url || !this.target) {
      console.warn("Autocomplete: configuration manquante", input);
      return;
    }

    this.init();
  }

  init() {
    console.log("Autocomplete instancié sur input:", this.input);

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

      if (!Array.isArray(data.results)) {
        console.warn("Autocomplete: format de résultats invalide", data);
        this.target.innerHTML = "";
        return;
      }

      if (data.results.length === 0) {
        this.target.innerHTML = `<div class="${this.noResultsClass}">Aucun résultat</div>`;
        return;
      }

      let html = "";

      data.results.forEach(item => {
        let label = item.label;

        if (this.highlight) {
          // Highlight texte saisi
          const regex = new RegExp(`(${query})`, "gi");
          label = label.replace(regex, `<strong>$1</strong>`);
        }

        // Transformation en lien si itemUrlTemplate précisé et item.id présent
        if (this.itemUrlTemplate && item.id) {
          const url = this.itemUrlTemplate.replace("ID_PLACEHOLDER", item.id);
          html += `<a href="${url}" class="${this.linkClass} ${this.itemClass}">${label}</a>`;
        } else {
          html += `<div class="${this.itemClass}">${label}</div>`;
        }
      });

      this.target.innerHTML = html;
    } catch (e) {
      console.error("Autocomplete error:", e);
      this.target.innerHTML = "";
    }
  }
}

/**
 * Fonction utilitaire pour initialiser tous les inputs avec data-autocomplete
 * Peut être appelée après injection AJAX
 */
export function initAutocomplete() {
  document
    .querySelectorAll("input[data-autocomplete='true']")
    .forEach(input => {
      if (!input.dataset.autocompleteInitialized) {
        new Autocomplete(input);
        input.dataset.autocompleteInitialized = "true";
      }
    });
}
