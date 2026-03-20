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
// Initialisation des filtres véhicules avec guard
let filtersInitialized = false;

function waitForVehicleFilters() {
  const form = document.getElementById("filters-form");
  if (form && !filtersInitialized) {
    initVehicleFilters();
    filtersInitialized = true;
  } else if (!filtersInitialized) {
    // Observer uniquement pour l’insertion du formulaire (sidebar dynamique)
    const observer = new MutationObserver(() => {
      const form = document.getElementById("filters-form");
      if (form && !filtersInitialized) {
        initVehicleFilters();
        filtersInitialized = true;
        observer.disconnect();
      }
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }
}

document.addEventListener("DOMContentLoaded", waitForVehicleFilters);
document.addEventListener("visibilitychange", () => {
  if (document.visibilityState === "visible") waitForVehicleFilters();
});

console.log("app.js chargé et véhiculesFilters prêt à fonctionner ");
