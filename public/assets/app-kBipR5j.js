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
import EventBus from "./js/EventBus.js";

console.log("APP JS LOADED");

/**
 * Sidebar
 */
function initSidebars() {
  initSidebar?.();
}

/**
 * Sliders double range
 */
function initSliders() {
  document.querySelectorAll(".double-slider").forEach(el => {
    if (el.dataset.initialized === "1") return;

    el.dataset.initialized = "1";
    initDoubleSlider?.(el);
  });
}

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
 * Filters (sidebar véhicule)
 */
function initVehicleFilters() {
  document.querySelectorAll("#filters-form").forEach(form => {
    if (form.dataset.initialized === "1") return;

    form.dataset.initialized = "1";
    new VehiclesFilter(form);
  });
}

/**
 * Dynamic collections
 */
function initCollections() {
  document.querySelectorAll("[data-collection]").forEach(el => {
    if (el.dataset.initialized === "1") return;

    el.dataset.initialized = "1";
    new DynamicFormCollection(el);
  });
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
 * FetchForms (listing AJAX)
 */
function initFetchForms() {
  document.querySelectorAll("[data-module='fetch-form']").forEach(form => {
    if (form.dataset.initialized === "1") return;

    form.dataset.initialized = "1";
    new FetchForm(form);
  });
}

/**
 * Autocomplete
 */
function initAutocomplete() {
  document.querySelectorAll("[data-autocomplete]").forEach(input => {
    if (input.dataset.initialized === "1") return;

    input.dataset.initialized = "1";
    new Autocomplete(input);
  });
}

/**
 * Ajax Manager (modales)
 */
function initAjaxManager() {
  if (!window.ajaxManager) {
    window.ajaxManager = new AjaxManager();
  }
}

/**
 * Global init
 */
function init() {
  initSidebars();
  initSliders();
  initDropzones();
  initVehicleFilters();
  initCollections();
  initFavorites();

  initFetchForms();
  initAutocomplete();
  initAjaxManager();
}

/**
 * DOM ready
 */
document.addEventListener("DOMContentLoaded", init);

/**
 * Re-init après AJAX
 */
EventBus.on("ui:updated", () => {
  initFetchForms();
  initAutocomplete();
  initFavorites();
});
