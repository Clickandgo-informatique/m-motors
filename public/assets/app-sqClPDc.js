import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";

import DynamicFormCollection from "./js/DynamicFormCollection.js";
import FetchForm from "./js/FetchForm.js";

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("[data-collection]").forEach(root => {
    new DynamicFormCollection(root);
  });
});

/**
 * Initialise tous les champs utilisant FetchForm
 */
function initFetchForms() {
  // ⚠️ On cible UNIQUEMENT les inputs qui ont un dropdown associé
  document.querySelectorAll("input[data-result-div]").forEach(input => {
    new FetchForm(input);
  });
}

document.addEventListener("DOMContentLoaded", initFetchForms);

window.addEventListener("pageshow", event => {
  if (event.persisted) {
    initFetchForms();
  }
});

// Hack anti-bfcache (optionnel, tu peux le garder)
window.addEventListener("unload", () => {});

console.log("This log comes from assets/app.js - welcome to AssetMapper! 🎉");
