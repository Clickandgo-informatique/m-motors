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
 * Debug helper
 */
// function debug(label, data = null) {
//   console.log(`[DEBUG ${label}]`, data ?? "");
// }

/**
 * Dropzones
 */
function initDropzones() {
  document.querySelectorAll(".dropzone").forEach(el => {
    if (el.dataset.initialized === "1") return;

    el.dataset.initialized = "1";
    new Dropzone(el);
  });
}

/**
 * Filters
 */
function initFilters() {
  const form = document.getElementById("filters-form");
  if (!form) return;
  if (form.dataset.initialized === "1") return;

  form.dataset.initialized = "1";
  new VehiclesFilter(form);
}

/**
 * Favorites
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
  document
    .querySelectorAll('input[data-autocomplete="true"]')
    .forEach(input => {
      if (input.dataset.initialized === "1") return;

      input.dataset.initialized = "1";
      new Autocomplete(input);
    });
}

/**
 * Fetch forms (IMPORTANT FIX)
 */
function initFetchForms() {
  FetchForm.initAll();
}

/**
 * Collections dynamiques
 */
function initCollections() {
  document.querySelectorAll("[data-collection]").forEach(root => {
    if (root.dataset.initialized === "1") return;

    root.dataset.initialized = "1";
    new DynamicFormCollection(root);
  });
}

/**
 * Ajax manager
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
  document.querySelectorAll(".double-slider").forEach(slider => {
    if (slider.dataset.initialized === "1") return;

    slider.dataset.initialized = "1";
    initDoubleSlider(slider);
  });
}

/**
 * App init
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
 * Re-init AJAX safe
 */
window.addEventListener("ui:updated", () => {
  debug("UI UPDATED");

  document.querySelectorAll("[data-fetch-init]").forEach(el => {
    el.removeAttribute("data-fetch-init");
  });

  initApp();
});
