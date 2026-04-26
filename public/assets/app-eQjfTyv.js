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
// TRACKERS (ANTI DOUBLE INIT)
// =========================================================

const filtersForms = new Set();
const favsInitialized = new WeakSet();
const dropzonesInitialized = new WeakSet();

// IMPORTANT : on passe à un flag dataset (plus fiable que WeakSet)
const AUTOCOMPLETE_FLAG = "autocompleteInitialized";

// =========================================================
// DROPZONE INIT
// =========================================================

function initDropzones() {
  document.querySelectorAll(".dropzone").forEach(el => {
    if (!dropzonesInitialized.has(el)) {
      new Dropzone(el);
      dropzonesInitialized.add(el);
    }
  });
}

// =========================================================
// FILTERS INIT
// =========================================================

function initFilters() {
  const form = document.getElementById("filters-form");

  if (form && !filtersForms.has(form)) {
    new VehiclesFilter(form);
    filtersForms.add(form);
  }
}

// =========================================================
// FAVORITES INIT
// =========================================================

function initFavorites() {
  document.querySelectorAll('[data-action="toggle-favorite"]').forEach(btn => {
    if (!favsInitialized.has(btn)) {
      new ToggleVehicleFavorite(btn);
      favsInitialized.add(btn);
    }
  });
}

// =========================================================
// AUTOCOMPLETE INIT (VERSION SAFE UNIQUE)
// =========================================================

function initAutocomplete() {
  document.querySelectorAll('[data-autocomplete="true"]').forEach(input => {
    if (!(input instanceof HTMLInputElement)) return;

    // anti double init robuste (même si DOM recréé)
    if (input.dataset[AUTOCOMPLETE_FLAG] === "1") return;

    new Autocomplete(input);

    input.dataset[AUTOCOMPLETE_FLAG] = "1";
  });
}

// =========================================================
// FETCHFORM INIT
// =========================================================

function initFetchForms() {
  document.querySelectorAll("input[data-result-div]").forEach(input => {
    if (!input.dataset.fetchFormInitialized) {
      new FetchForm(input);
      input.dataset.fetchFormInitialized = "true";
    }
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
// MUTATION OBSERVER (IMPORTANT : FIXÉ)
// =========================================================

const observerConfig = {
  childList: true,
  subtree: true
};

// ❌ IMPORTANT FIX : on NE REINITIALISE PLUS AUTOCOMPLETE
// sinon double instances + texte parasite

const filtersObserver = new MutationObserver(initFilters);
filtersObserver.observe(document.body, observerConfig);

const favoritesObserver = new MutationObserver(initFavorites);
favoritesObserver.observe(document.body, observerConfig);

const dropzoneObserver = new MutationObserver(initDropzones);
dropzoneObserver.observe(document.body, observerConfig);

// =========================================================
// VISIBILITY CHANGE (SAFE)
// =========================================================

document.addEventListener("visibilitychange", () => {
  if (document.visibilityState === "visible") {
    initFetchForms();
    initFilters();
    initFavorites();
    initDropzones();

    // ❌ on NE relance PAS autocomplete ici
  }
});
