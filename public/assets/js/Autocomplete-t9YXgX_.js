// assets/js/Autocomplete.js

/**
 * Module Autocomplete
 *
 * Prend un <input data-autocomplete="true"> et renvoie les résultats
 * dans un conteneur cible. Rafraîchit aussi la galerie de cards.
 *
 * Dataset attendus sur l'input :
 * - data-url : URL du endpoint autocomplete
 * - data-target : selector du conteneur où injecter les résultats
 * - data-item-url (optionnel) : si présent, transforme chaque résultat en <a href="...">
 * - data-result-cards (optionnel) : selector du container de cards à mettre à jour
 *
 * Compatible AJAX FetchForm + pagination automatique
 */

export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url;
    this.target = document.querySelector(input.dataset.target);
    this.itemUrlField = input.dataset.itemUrl || null;
    this.cardsContainer = input.closest("form")?.dataset.resultCards
      ? document.querySelector(input.closest("form").dataset.resultCards)
      : null;

    if (!this.url || !this.target) {
      console.warn("Autocomplete: config manquante sur input", input);
      return;
    }

    this.debounce = null;
    this.init();
  }

  init() {
    console.log("Autocomplete initialisé pour", this.input);

    // Événement input avec debounce
    this.input.addEventListener("input", e => {
      const value = e.target.value.trim();
      clearTimeout(this.debounce);

      if (value.length < 2) {
        this.clearResults();
        return;
      }

      this.debounce = setTimeout(() => this.fetch(value), 250);
    });
  }

  async fetch(query) {
    try {
      const res = await fetch(
        `${this.url}?q=${encodeURIComponent(query)}&autocomplete=true`
      );
      const data = await res.json();

      // Vérification du format JSON attendu
      if (!data.items && !data.results) {
        console.warn("Autocomplete: format de résultats invalide", data);
        this.clearResults();
        return;
      }

      // --- Transformation des résultats en <ul><li> ---
      let html = "<ul class='autocomplete-list'>";
      const items = data.items || data.results;
      items.forEach(item => {
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

      // --- Mise à jour de la galerie de cards si container spécifié ---
      if (this.cardsContainer && data.resultsHtml) {
        this.cardsContainer.innerHTML = data.resultsHtml;

        // Ré-init boutons favoris dans les cards mises à jour
        document
          .querySelectorAll('[data-action="toggle-favorite"]')
          .forEach(btn => {
            if (!btn.dataset.favoriteInitialized) {
              import("./ToggleVehicleFavorite.js").then(module => {
                new module.default(btn);
                btn.dataset.favoriteInitialized = "true";
              });
            }
          });
      }

      // --- Scroll automatique vers le début des résultats ---
      this.target.scrollIntoView({ behavior: "smooth", block: "start" });
    } catch (err) {
      console.error("Autocomplete error:", err);
      this.clearResults();
    }
  }

  clearResults() {
    if (this.target) this.target.innerHTML = "";
  }
}
