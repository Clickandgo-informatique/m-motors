import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";

import initSidebar from "./js/sidebar.js";
import "./js/rangeSelector.js";

import Dropzone from "./js/Dropzone.js";

import VehiclesFilter from "./js/vehiclesFilters.js";
import DynamicFormCollection from "./js/DynamicFormCollection.js";
import FetchForm from "./js/FetchForm.js";
import AjaxManager from "./js/AjaxManager.js";
import ToggleVehicleFavorite from "./js/ToggleVehicleFavorite.js";
import Autocomplete from "./js/Autocomplete.js";

// Protection anti double init global
let appInitialized = false;
console.log("FILTER FORM:", document.getElementById("filters-form"));
/**
 * Safe wrapper pour éviter qu'un module casse toute l'app
 */
function safeInit(fn, label = "module") {
  try {
    fn();
  } catch (e) {
    console.error(`Erreur init ${label}:`, e);
  }
}

/**
 * Initialisation Dropzone
 */
function initDropzones() {
  document.querySelectorAll(".dropzone").forEach(el => {
    if (el.dataset.initialized === "1") return;
    el.dataset.initialized = "1";
    new Dropzone(el);
  });
}

/**
 * Init filtres véhicules
 */
function initFilters() {
  const form = document.getElementById("filters-form");
  if (!form) return;

  if (form.dataset.initialized === "1") return;
  form.dataset.initialized = "1";

  new VehiclesFilter(form);
}

/**
 * Favoris
 */
function initFavorites() {
  document.querySelectorAll('[data-action="toggle-favorite"]').forEach(btn => {
    if (btn.dataset.initialized === "1") return;
    btn.dataset.initialized = "1";

    new ToggleVehicleFavorite(btn);
  });
}

/**
 * Autocomplete
 */
function initAutocomplete() {
  document.querySelectorAll('[data-autocomplete="true"]').forEach(input => {
    if (!(input instanceof HTMLInputElement)) return;
    if (input.dataset.initialized === "1") return;

    input.dataset.initialized = "1";
    new Autocomplete(input);
  });
}

/**
 * Fetch forms
 */
function initFetchForms() {
  document.querySelectorAll("[data-fetch-form]").forEach(form => {
    if (form.dataset.initialized === "1") return;

    const input = form.querySelector('input[name="q"]');
    if (!input) return;

    form.dataset.initialized = "1";
    new FetchForm(input);
  });
}

/**
 * Dynamic collections
 */
function initCollections() {
  document.querySelectorAll("[data-collection]").forEach(root => {
    if (root.dataset.initialized === "1") return;
    root.dataset.initialized = "1";

    new DynamicFormCollection(root);
  });
}

/**
 * Ajax manager singleton
 */
function initAjaxManager() {
  if (!window.AjaxManagerInstance) {
    window.AjaxManagerInstance = new AjaxManager();
  }
}

/**
 * App init principal
 */
function initApp() {
  if (appInitialized) return;
  appInitialized = true;

  safeInit(initSidebar, "sidebar");
  safeInit(initDropzones, "dropzones");
  safeInit(initFilters, "filters");
  safeInit(initFavorites, "favorites");
  safeInit(initAutocomplete, "autocomplete");
  safeInit(initFetchForms, "fetchForms");
  safeInit(initCollections, "collections");
  safeInit(initAjaxManager, "ajaxManager");

  console.log("app.js initialisé");
}

/**
 * DOM ready
 */
document.addEventListener("DOMContentLoaded", initApp);

/**
 * Re-init après AJAX global
 */
window.addEventListener("ui:updated", () => {
  appInitialized = false;
  initApp();
});
