// assets/js/Autocomplete.js

/**
 * Module Autocomplete
 *
 * Gère un input avec autocomplétion.
 * Les résultats sont affichés sous forme de liste de liens dans un conteneur cible.
 * Si l'input est intégré dans un FetchForm lié à une galerie de cards, la galerie est aussi mise à jour.
 *
 * Dataset attendus sur l'input :
 * - data-url : URL du endpoint autocomplete / recherche AJAX
 * - data-target : selector du conteneur où injecter les résultats
 * - data-item-url (optionnel) : si présent, chaque résultat est transformé en <a href="...">
 * - data-result-cards (optionnel) : selector du container gallery pour mise à jour
 */
export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url; // endpoint AJAX
    this.target = document.querySelector(input.dataset.target); // conteneur résultats
    this.itemUrlField = input.dataset.itemUrl || null; // clé URL
    this.cardsContainerSelector = input.dataset.resultCards || null; // container cards
    this.debounce = null;

    if (!this.url || !this.target) {
      console.warn("Autocomplete: configuration manquante sur l'input", input);
      return;
    }

    this.init();
  }

  /**
   * Initialisation : écoute l'input et déclenche fetch après debounce
   */
  init() {
    console.log("Autocomplete initialisé pour", this.input);

    this.input.addEventListener("input", e => {
      const value = e.target.value.trim();

      // reset debounce
      clearTimeout(this.debounce);

      // si moins de 2 caractères, vide le container et stop
      if (value.length < 2) {
        this.clearResults();
        return;
      }

      // debounce 250ms
      this.debounce = setTimeout(() => this.fetch(value), 250);
    });
  }

  /**
   * Vide le conteneur de résultats
   */
  clearResults() {
    this.target.innerHTML = "";
  }

  /**
   * Fetch AJAX vers l'endpoint
   */
  async fetch(query) {
    try {
      const res = await fetch(`${this.url}?q=${encodeURIComponent(query)}`);
      const data = await res.json();

      // --- Vérification du format ---
      if (!data.items && !data.results) {
        console.warn("Autocomplete: format de résultats invalide", data);
        this.clearResults();
        return;
      }

      // --- Résultats autocomplete ---
      const items = data.items || []; // fallback sur items
      if (!Array.isArray(items)) {
        console.warn("Autocomplete: items n'est pas un tableau", items);
        this.clearResults();
        return;
      }

      // Construction HTML <ul><li>
      const ul = document.createElement("ul");
      ul.className = "autocomplete-list";

      items.forEach(item => {
        const li = document.createElement("li");
        li.className = "autocomplete-item";

        if (this.itemUrlField && item[this.itemUrlField]) {
          const a = document.createElement("a");
          a.href = item[this.itemUrlField];
          a.textContent = item.label;
          a.className = "autocomplete-link";
          li.appendChild(a);
        } else {
          li.textContent = item.label;
        }

        ul.appendChild(li);
      });

      // Injection dans le container
      this.target.innerHTML = "";
      this.target.appendChild(ul);

      // --- Mise à jour des cards si container précisé ---
      if (this.cardsContainerSelector && data.results) {
        const container = document.querySelector(this.cardsContainerSelector);
        if (container) {
          container.innerHTML = data.results; // injecte la galerie complète
        }
      }
    } catch (e) {
      console.error("Autocomplete error:", e);
      this.clearResults();
    }
  }
}
