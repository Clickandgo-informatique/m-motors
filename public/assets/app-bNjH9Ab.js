// =========================================================
// CORE IMPORTS
// =========================================================

import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";
import "./js/sidebar.js";
import "./js/rangeSelector.js";

// Dropzone
import Dropzone from "./js/Dropzone.js";

// =========================================================
// FEATURE MODULES
// =========================================================

import VehiclesFilter from "./js/vehiclesFilters.js";
import DynamicFormCollection from "./js/DynamicFormCollection.js";
import FetchForm from "./js/FetchForm.js";
import AjaxManager from "./js/AjaxManager.js";
import ToggleVehicleFavorite from "./js/ToggleVehicleFavorite.js";
import Autocomplete from "./js/Autocomplete.js";

// =========================================================
// REGISTERS (ANTI DOUBLE INIT)
// =========================================================

const filtersForms = new Set();
const favsInitialized = new WeakSet();
const dropzonesInitialized = new WeakSet();

const AUTOCOMPLETE_FLAG = "autocompleteInitialized";

// =========================================================
// DROPZONE INIT
// =========================================================

function initDropzones() {
  document.querySelectorAll(".dropzone").forEach(el => {
    if (dropzonesInitialized.has(el)) return;

    new Dropzone(el);
    dropzonesInitialized.add(el);
  });
}

// =========================================================
// FILTERS INIT
// =========================================================

function initFilters() {
  const form = document.getElementById("filters-form");

  if (!form || filtersForms.has(form)) return;

  new VehiclesFilter(form);
  filtersForms.add(form);
}

// =========================================================
// FAVORITES INIT
// =========================================================

function initFavorites() {
  document.querySelectorAll('[data-action="toggle-favorite"]').forEach(btn => {
    if (favsInitialized.has(btn)) return;

    new ToggleVehicleFavorite(btn);
    favsInitialized.add(btn);
  });
}

// =========================================================
// AUTOCOMPLETE INIT
// =========================================================

function initAutocomplete() {
  document.querySelectorAll('[data-autocomplete="true"]').forEach(input => {
    if (!(input instanceof HTMLInputElement)) return;

    if (input.dataset[AUTOCOMPLETE_FLAG] === "1") return;

    new Autocomplete(input);

    input.dataset[AUTOCOMPLETE_FLAG] = "1";
  });
}

// =========================================================
// FETCHFORM INIT (CORRIGÉ SCOPING PROPRE)
// =========================================================

function initFetchForms() {
  document.querySelectorAll("[data-fetch-form]").forEach(form => {
    if (form.dataset.fetchFormInitialized) return;

    const input = form.querySelector('input[name="q"]');
    if (!input) return;

    new FetchForm(input);

    form.dataset.fetchFormInitialized = "true";
  });
}

// =========================================================
// GLOBAL INIT
// =========================================================

function initApp() {
  document.querySelectorAll("[data-collection]").forEach(root => {
    new DynamicFormCollection(root);
  });

  initDropzones();
  initFilters();
  initFavorites();
  initAutocomplete();
  initFetchForms();

  if (!window.AjaxManagerInstance) {
    window.AjaxManagerInstance = new AjaxManager();
  }

  console.log("app.js initialisé");
}

// =========================================================
// DOM READY
// =========================================================

document.addEventListener("DOMContentLoaded", initApp);

// =========================================================
// MUTATION OBSERVER (SCOPED UNIQUEMENT AJAX)
// =========================================================

// IMPORTANT : on limite l'observer au container AJAX
const ajaxRoot = document.getElementById("vehicles-search-results");

if (ajaxRoot) {
  const observer = new MutationObserver(() => {
    initFavorites();
    initFetchForms();
  });

  observer.observe(ajaxRoot, {
    childList: true,
    subtree: true
  });
}

// =========================================================
// VISIBILITY CHANGE
// =========================================================

document.addEventListener("visibilitychange", () => {
  if (document.visibilityState !== "visible") return;

  initDropzones();
  initFilters();
  initFavorites();
  initFetchForms();
});
