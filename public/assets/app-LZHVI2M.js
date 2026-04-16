// =========================================================
// CORE IMPORTS
// =========================================================

import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";
import "./js/sidebar.js";
import "./js/rangeSelector.js";

// Dropzone (M-MOTORS FIX)
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
// INIT TRACKERS (ANTI DOUBLE INIT)
// =========================================================

const filtersForms = new Set();
const favsInitialized = new WeakSet();
const autocompleteInitialized = new WeakSet();
const dropzonesInitialized = new WeakSet();

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
    console.log("VehiclesFilter initialisé");
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
// AUTOCOMPLETE INIT
// =========================================================

function initAutocomplete() {
  document.querySelectorAll('[data-autocomplete="true"]').forEach(input => {
    if (!autocompleteInitialized.has(input)) {
      new Autocomplete(input);
      autocompleteInitialized.add(input);
    }
  });
}

// =========================================================
// FETCHFORM INIT (one-shot safe)
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
// DOM READY
// =========================================================

document.addEventListener("DOMContentLoaded", () => {
  // -------------------------------
  // Collections dynamiques Symfony
  // -------------------------------
  document.querySelectorAll("[data-collection]").forEach(root => {
    new DynamicFormCollection(root);
  });

  // -------------------------------
  // INIT MODULES
  // -------------------------------
  initDropzones();
  initFilters();
  initFavorites();
  initAutocomplete();
  initFetchForms();

  // -------------------------------
  // AJAX GLOBAL MANAGER
  // -------------------------------
  window.AjaxManagerInstance = new AjaxManager();

  console.log("app.js chargé : modules initialisés");
});

// =========================================================
// MUTATION OBSERVERS (DOM DYNAMIQUE)
// =========================================================

const observersConfig = {
  childList: true,
  subtree: true
};

const filtersObserver = new MutationObserver(() => initFilters());
filtersObserver.observe(document.body, observersConfig);

const favoritesObserver = new MutationObserver(() => initFavorites());
favoritesObserver.observe(document.body, observersConfig);

const autocompleteObserver = new MutationObserver(() => initAutocomplete());
autocompleteObserver.observe(document.body, observersConfig);

const dropzoneObserver = new MutationObserver(() => initDropzones());
dropzoneObserver.observe(document.body, observersConfig);

// =========================================================
// VISIBILITY CHANGE (TAB SWITCH)
// =========================================================

document.addEventListener("visibilitychange", () => {
  if (document.visibilityState === "visible") {
    initFetchForms();
    initFilters();
    initFavorites();
    initAutocomplete();
    initDropzones();
  }
});
