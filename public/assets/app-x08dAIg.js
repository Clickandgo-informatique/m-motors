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

console.log("app.js initialisé");

/* ==========================================================
   DROPZONES
========================================================== */
function initDropzones() {
  document.querySelectorAll(".dropzone").forEach(el => {
    if (el.dataset.initialized === "1") return;

    el.dataset.initialized = "1";
    new Dropzone(el);
  });
}

/* ==========================================================
   FILTRES VEHICULES
========================================================== */
function initFilters() {
  const form = document.getElementById("filters-form");
  if (!form) return;

  if (form.dataset.initialized === "1") return;

  form.dataset.initialized = "1";
  new VehiclesFilter(form);
}

/* ==========================================================
   FAVORIS
========================================================== */
function initFavorites() {
  document.querySelectorAll('[data-action="toggle-favorite"]').forEach(btn => {
    if (btn.dataset.initialized === "1") return;

    btn.dataset.initialized = "1";
    new ToggleVehicleFavorite(btn);
  });
}

/* ==========================================================
   AUTOCOMPLETE
   IMPORTANT : pas de data-initialized (DOM AJAX friendly)
========================================================== */
function initAutocomplete() {
  document.querySelectorAll("[data-module='autocomplete']").forEach(input => {
    new Autocomplete(input);
  });
}

/* ==========================================================
   FETCH FORMS
========================================================== */
function initFetchForms() {
  document.querySelectorAll("[data-module='fetch-form']").forEach(form => {
    if (form.dataset.initialized === "1") return;

    form.dataset.initialized = "1";

    try {
      new FetchForm(form);
    } catch (e) {
      console.error("[initFetchForms] error", e, form);
    }
  });
}

/* ==========================================================
   COLLECTIONS DYNAMIQUES
========================================================== */
function initCollections() {
  document.querySelectorAll("[data-collection]").forEach(root => {
    if (root.dataset.initialized === "1") return;

    root.dataset.initialized = "1";
    new DynamicFormCollection(root);
  });
}

/* ==========================================================
   AJAX MANAGER (MODALES)
========================================================== */
function initAjaxManager() {
  window.ajaxManager = new AjaxManager();
}

/* ==========================================================
   SLIDERS
========================================================== */
function initSliders() {
  document.querySelectorAll(".double-slider").forEach(slider => {
    if (slider.dataset.initialized === "1") return;

    slider.dataset.initialized = "1";
    initDoubleSlider(slider);
  });
}

/* ==========================================================
   INIT PRINCIPAL
========================================================== */
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

/* ==========================================================
   DOM READY
========================================================== */
document.addEventListener("DOMContentLoaded", initApp);

/* ==========================================================
   REINIT APRES AJAX
========================================================== */
EventBus.on("ui:updated", () => {
  initAutocomplete();
  initFetchForms();
  initFavorites();
  initCollections();
  initSliders();
  initAjaxManager();
});
