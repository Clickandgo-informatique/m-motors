import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";

// Gestion des formulaires dynamiques
import DynamicFormCollection from "./js/DynamicFormCollection.js";
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("[data-collection]").forEach(root => {
    new DynamicFormCollection(root);
  });
});

// Recherche dans les formulaires
import FetchForm from "./js/FetchForm.js";

/**
 * Initialise tous les champs utilisant FetchForm
 */
function initFetchForms() {
  document.querySelectorAll("[data-search-form]").forEach(input => {
    new FetchForm(input);
  });
}

/**
 * Exécution normale au chargement de la page
 */
document.addEventListener("DOMContentLoaded", initFetchForms);

/**
 * Gestion du Back/Forward Cache (bfcache)
 */
window.addEventListener("pageshow", event => {
  if (event.persisted) {
    initFetchForms();
  }
});

console.log("This log comes from assets/app.js - welcome to AssetMapper! 🎉");
