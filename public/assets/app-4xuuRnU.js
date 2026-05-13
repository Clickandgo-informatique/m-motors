import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";

import initSidebar from "./js/sidebar.js";
import initDoubleSlider from "./js/rangeSelector.js";
import Dropzone from "./js/Dropzone.js";
import DynamicFormCollection from "./js/DynamicFormCollection.js";
import FetchForm from "./js/FetchForm.js";
import AjaxManager from "./js/AjaxManager.js";
import ToggleVehicleFavorite from "./js/ToggleVehicleFavorite.js";
import Autocomplete from "./js/Autocomplete.js";
import EventBus from "./js/EventBus.js";
import VehiclesFilters from "./js/VehiclesFilters.js";

import VehicleFilterStore from "./js/VehicleFilterStore.js";

console.log("app.js initialisé");

function resetInitFlags(root) {
  root.querySelectorAll("[data-fetch-form-initialized]").forEach(el => {
    el.removeAttribute("data-fetch-form-initialized");
  });

  root.querySelectorAll("[data-filters-initialized]").forEach(el => {
    el.removeAttribute("data-filters-initialized");
  });

  root.querySelectorAll("[data-autocomplete-initialized]").forEach(el => {
    el.removeAttribute("data-autocomplete-initialized");
  });

  root.querySelectorAll("[data-slider-initialized]").forEach(el => {
    el.removeAttribute("data-slider-initialized");
  });
}

/*
 * Store global des filtres véhicules
 */
window.vehicleStore = new VehicleFilterStore();

/*
 * Dropzones
 */
function initDropzones(root = document) {
  root.querySelectorAll(".dropzone").forEach(el => {
    if (el.dataset.dropzoneInitialized === "1") return;

    el.dataset.dropzoneInitialized = "1";
    new Dropzone(el);
  });
}

/*
 * Filtres véhicules
 */
function initFilters(root = document) {
  const form = root.querySelector("#filters-form");
  if (!form) return;

  if (form.dataset.filtersInitialized === "1") return;

  form.dataset.filtersInitialized = "1";

  new VehiclesFilters(form, window.vehicleStore);
}

/*
 * Favoris
 */
function initFavorites(root = document) {
  root.querySelectorAll('[data-action="toggle-favorite"]').forEach(btn => {
    if (btn.dataset.favoriteInitialized === "1") return;

    btn.dataset.favoriteInitialized = "1";
    new ToggleVehicleFavorite(btn);
  });
}

/*
 * Autocomplete
 */
function initAutocomplete(root = document) {
  root.querySelectorAll("[data-module='autocomplete']").forEach(input => {
    if (input.dataset.autocompleteInitialized === "1") return;

    input.dataset.autocompleteInitialized = "1";
    new Autocomplete(input);
  });
}

/*
 * Fetch forms AJAX
 */
function initFetchForms(root = document) {
  root.querySelectorAll("[data-module='fetch-form']").forEach(form => {
    if (form.dataset.fetchFormInitialized === "1") return;

    form.dataset.fetchFormInitialized = "1";
    new FetchForm(form);
  });
}

/*
 * Dynamic collections Symfony forms
 */
function initCollections(root = document) {
  root.querySelectorAll("[data-collection]").forEach(el => {
    if (el.dataset.collectionInitialized === "1") return;

    el.dataset.collectionInitialized = "1";
    new DynamicFormCollection(el);
  });
}

/*
 * Sliders
 */
function initSliders(root = document) {
  root.querySelectorAll(".double-slider").forEach(slider => {
    if (slider.dataset.sliderInitialized === "1" && !slider.dataset.forceReinit) {
      return;
    }

    slider.dataset.sliderInitialized = "1";
    delete slider.dataset.forceReinit;

    initDoubleSlider(slider);
  });
}

/*
 * AJAX manager global
 */
function initAjaxManager() {
  if (!window.ajaxManager) {
    window.ajaxManager = new AjaxManager();
  }
}

/*
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

/*
 * DOM ready
 */
document.addEventListener("DOMContentLoaded", initApp);

/*
 * Re-init après mise à jour UI AJAX
 */
EventBus.on("ui:updated", ({ target }) => {
  const root = target || document;

  resetInitFlags(root);

  initAutocomplete(root);
  initFetchForms(root);
  initFavorites(root);
  initCollections(root);
  initSliders(root);
});
