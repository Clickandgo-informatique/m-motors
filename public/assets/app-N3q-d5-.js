/**
 * assets/app.js
 * ------------------------------------------------------------------
 * Architecture modulaire basée sur data-module
 *
 * Objectifs :
 * - un seul système d'initialisation
 * - zéro double binding
 * - compatible ui:updated
 * ------------------------------------------------------------------
 */

import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";

import Sidebar from "./js/sidebar.js";
import initDoubleSlider from "./js/rangeSelector.js";

import Dropzone from "./js/Dropzone.js";
import VehiclesFilter from "./js/vehiclesFilters.js";
import DynamicFormCollection from "./js/DynamicFormCollection.js";
import FetchForm from "./js/FetchForm.js";
import AjaxManager from "./js/AjaxManager.js";
import ToggleVehicleFavorite from "./js/ToggleVehicleFavorite.js";
import Autocomplete from "./js/Autocomplete.js";

/**
 * Registry central des modules
 */
const modules = {
  dropzone: Dropzone,
  "vehicles-filter": VehiclesFilter,
  "fetch-form": FetchForm,
  autocomplete: Autocomplete,
  "favorite-toggle": ToggleVehicleFavorite,
  collection: DynamicFormCollection,
  "double-slider": initDoubleSlider
};

/**
 * Initialisation générique
 */
function initModules(root = document) {
  Object.entries(modules).forEach(([selector, Module]) => {
    root.querySelectorAll(`[data-module="${selector}"]`).forEach(el => {
      if (el.__moduleInitialized) return;

      el.__moduleInitialized = true;

      new Module(el);
    });
  });
}

/**
 * Modules globaux (singletons)
 */
function initSingletons() {
  Sidebar?.();

  if (!window.AjaxManagerInstance) {
    window.AjaxManagerInstance = new AjaxManager();
  }
}

/**
 * Init application
 */
function initApp() {
  initSingletons();
  initModules();
}

/**
 * DOM ready
 */
document.addEventListener("DOMContentLoaded", initApp);

/**
 * Réinit après AJAX
 */
window.addEventListener("ui:updated", () => {
  initModules(document);
});
