// assets/app.js

import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";
import "./js/sidebar.js";
import "./js/rangeSelector.js";
import { initVehicleFilters } from "./js/vehiclesFilters.js";

import DynamicFormCollection from "./js/DynamicFormCollection.js";
import FetchForm from "./js/FetchForm.js";
import AjaxManager from "./js/AjaxManager.js";

// --------------------------------------------------
// DynamicFormCollection
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("[data-collection]").forEach(root => {
    new DynamicFormCollection(root);
  });
});

// --------------------------------------------------
// FetchForm
function initFetchForms() {
  document.querySelectorAll("input[data-result-div]").forEach(input => {
    new FetchForm(input);
  });
}

document.addEventListener("DOMContentLoaded", initFetchForms);
document.addEventListener("visibilitychange", () => {
  if (document.visibilityState === "visible") initFetchForms();
});

// --------------------------------------------------
// AjaxManager
document.addEventListener("DOMContentLoaded", () => new AjaxManager());

// --------------------------------------------------
// Initialisation des filtres véhicules
function initAllVehicleFilters() {
  // On initialise sliders et checkboxes si la sidebar est présente
  initVehicleFilters();
}

// Chargement initial
document.addEventListener("DOMContentLoaded", initAllVehicleFilters);

// Pour navigation "back/forward" (bfcache)
document.addEventListener("visibilitychange", () => {
  if (document.visibilityState === "visible") initAllVehicleFilters();
});

// Observer pour inclusions dynamiques (sidebar ajoutée plus tard)
const observer = new MutationObserver(() => {
  if (document.getElementById("filters-form")) {
    initAllVehicleFilters();
    observer.disconnect();
  }
});
observer.observe(document.body, { childList: true, subtree: true });

console.log("app.js chargé et véhiculesFilters prêt à fonctionner ✅");
