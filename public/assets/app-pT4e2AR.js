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
import initDoubleSlider from "./js/rangeSelector.js";

/**
 * Protection globale contre double initialisation de l'app
 */
let appInitialized = false;

/**
 * Wrapper de sécurité pour éviter qu'un module casse toute l'app
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
 * Initialisation filtres véhicules
 */
function initFilters() {
  const form = document.getElementById("filters-form");
  if (!form) return;

  if (form.dataset.initialized === "1") return;

  form.dataset.initialized = "1";
  new VehiclesFilter(form);
}

/**
 * Initialisation favoris
 */
function initFavorites() {
  document.querySelectorAll('[data-action="toggle-favorite"]').forEach(btn => {
    if (btn.dataset.initialized === "1") return;

    btn.dataset.initialized = "1";
    new ToggleVehicleFavorite(btn);
  });
}

/**
 * Initialisation autocomplete
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
 * Initialisation des formulaires fetch AJAX
 */
function initFetchForms() {
  document.querySelectorAll("[data-fetch-form]").forEach(form => {
    if (form.dataset.initialized === "1") return;

    form.dataset.initialized = "1";

    new FetchForm(form);
  });
}

/**
 * Initialisation des collections dynamiques de formulaires
 */
function initCollections() {
  document.querySelectorAll("[data-collection]").forEach(root => {
    if (root.dataset.initialized === "1") return;

    root.dataset.initialized = "1";
    new DynamicFormCollection(root);
  });
}

/**
 * Initialisation du gestionnaire AJAX global (singleton)
 */
function initAjaxManager() {
  if (!window.AjaxManagerInstance) {
    window.AjaxManagerInstance = new AjaxManager();
  }
}

/**
 * Initialisation des sliders double range
 */
function initSliders() {
  document.querySelectorAll(".double-slider").forEach(slider => {
    if (slider.dataset.initialized === "1") return;

    slider.dataset.initialized = "1";

    try {
      initDoubleSlider(slider);
    } catch (e) {
      console.error("Slider init error:", e);
    }
  });
}

/**
 * Initialisation principale de l'application
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
  safeInit(initSliders, "sliders");

  console.log("app.js initialisé");
}

/**
 * DOM ready
 */
document.addEventListener("DOMContentLoaded", initApp);

/**
 * Réinitialisation après mise à jour AJAX globale
 */
window.addEventListener("ui:updated", () => {
  appInitialized = false;
  initApp();
});
