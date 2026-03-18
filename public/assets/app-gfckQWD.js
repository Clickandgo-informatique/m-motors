import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";
import "./js/sidebar.js";
import "./js/rangeSelector.js";
import "./js/vehiclesFilters.js";

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

// 1) Chargement normal
document.addEventListener("DOMContentLoaded", initFetchForms);
// 2) Retour arrière (Firefox, Safari, Chrome)
document.addEventListener("visibilitychange", () => {
  if (document.visibilityState === "visible") {
    initFetchForms();
  }
});

// Hack anti-bfcache (optionnel, tu peux le garder)
window.addEventListener("unload", () => {});

import AjaxManager from "./js/AjaxManager.js";
document.addEventListener("DOMContentLoaded", () => new AjaxManager());

console.log("This log comes from assets/app.js - welcome to AssetMapper! 🎉");
