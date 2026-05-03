/**
 * assets/app.js
 * ------------------------------------------------------------------
 * Initialisation globale M-Motors
 *
 * Objectifs :
 * - Initialiser chaque module une seule fois
 * - Réinitialiser uniquement les éléments injectés après AJAX
 * - Éviter les doubles bindings
 * ------------------------------------------------------------------
 */

import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";

import initSidebar from "./js/sidebar.js";
import initDoubleSlider from "./js/rangeSelector.js";

import Dropzone from "./js/Dropzone.js";
import VehiclesFilter from "./js/vehiclesFilters.js";
import DynamicFormCollection from "./js/DynamicFormCollection.js";
import FetchForm from "./js/FetchForm.js";
import AjaxManager from "./js/AjaxManager.js";
import ToggleVehicleFavorite from "./js/ToggleVehicleFavorite.js";
import Autocomplete from "./js/Autocomplete.js";

/**
 * Utilitaire générique d'initialisation
 * - évite duplication de code
 * - garantit un seul init par élément
 */
function initModule(selector, callback) {
  document.querySelectorAll(selector).forEach(el => {
    if (el.dataset.initialized === "1") return;

    el.dataset.initialized = "1";
    callback(el);
  });
}

/**
 * Dropzones
 */
function initDropzones() {
  initModule(".dropzone", el => new Dropzone(el));
}

/**
 * Filters
 */
function initFilters() {
  const form = document.getElementById("filters-form");
  if (!form || form.dataset.initialized === "1") return;

  form.dataset.initialized = "1";
  new VehiclesFilter(form);
}

/**
 * Favorites
 */
function initFavorites() {
  initModule('[data-action="toggle-favorite"]', el => {
    new ToggleVehicleFavorite(el);
  });
}

/**
 * Autocomplete
 */
function initAutocomplete() {
  initModule('input[data-autocomplete="true"]', el => {
    new Autocomplete(el);
  });
}

/**
 * FetchForms (exclusion des autocomplete)
 */
function initFetchForms() {
  initModule("form[data-fetch-form]:not([data-ignore-fetch='1'])", el => {
    new FetchForm(el);
  });
}

/**
 * Collections dynamiques
 */
function initCollections() {
  initModule("[data-collection]", el => {
    new DynamicFormCollection(el);
  });
}

/**
 * Ajax manager (singleton)
 */
function initAjaxManager() {
  if (!window.AjaxManagerInstance) {
    window.AjaxManagerInstance = new AjaxManager();
  }
}

/**
 * Sliders
 */
function initSliders() {
  initModule(".double-slider", el => {
    initDoubleSlider(el);
  });
}

/**
 * Initialisation globale
 */
function initApp() {
  initSidebar?.();
  initDropzones();
  initFilters();
  initFavorites();
  initAutocomplete();
  initFetchForms();
  initCollections();
  initAjaxManager();
  initSliders();
}

/**
 * DOM ready
 */
document.addEventListener("DOMContentLoaded", initApp);

/**
 * Réinitialisation après AJAX
 * - uniquement modules dépendant du DOM injecté
 */
window.addEventListener("ui:updated", () => {
  initAutocomplete();
  initFetchForms();
  initFavorites();
  initCollections();
  initSliders();
});
