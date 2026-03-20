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
  console.log("DOM fully loaded: Initialisation DynamicFormCollection");
  document.querySelectorAll("[data-collection]").forEach(root => {
    new DynamicFormCollection(root);
  });
});

// --------------------------------------------------
// FetchForm
function initFetchForms() {
  console.log("Initialisation FetchForm");
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
document.addEventListener("DOMContentLoaded", () => {
  console.log("Initialisation AjaxManager");
  new AjaxManager();
});

// --------------------------------------------------
// Initialisation des filtres véhicules
function initAllVehicleFilters() {
  console.log("initAllVehicleFilters appelé");
  const form = document.getElementById("filters-form");
  const results = document.getElementById("vehicles-index-results");

  if (form && results) {
    console.log(
      "Formulaire et container présents, lancement initVehicleFilters"
    );
    initVehicleFilters();
  } else {
    console.log(
      "initAllVehicleFilters: attente du DOM pour formulaire ou container..."
    );
  }
}

// 🔹 Fonction pour attendre que les deux éléments soient présents
function waitForVehicleFilters() {
  const form = document.getElementById("filters-form");
  const results = document.getElementById("vehicles-index-results");

  if (form && results) {
    initVehicleFilters();
    return;
  }

  // Observer pour attendre que les éléments soient présents
  const observer = new MutationObserver(() => {
    const form = document.getElementById("filters-form");
    const results = document.getElementById("vehicles-index-results");
    if (form && results) {
      console.log(
        "Formulaire et container détectés via MutationObserver, lancement initVehicleFilters"
      );
      initVehicleFilters();
      observer.disconnect();
    }
  });

  observer.observe(document.body, { childList: true, subtree: true });
}

// Appel initial
document.addEventListener("DOMContentLoaded", waitForVehicleFilters);
document.addEventListener("visibilitychange", () => {
  if (document.visibilityState === "visible") waitForVehicleFilters();
});

console.log("app.js chargé et véhiculesFilters prêt à fonctionner ✅");
