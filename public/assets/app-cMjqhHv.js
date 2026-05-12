import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";

import initSidebar from "./js/sidebar.js";
import initDoubleSlider from "./js/rangeSelector.js";
import Dropzone from "./js/Dropzone.js";
import VehiclesFilter from "./js/VehiclesFilters.js";
import DynamicFormCollection from "./js/DynamicFormCollection.js";
import FetchForm from "./js/FetchForm.js";
import AjaxManager from "./js/AjaxManager.js";
import ToggleVehicleFavorite from "./js/ToggleVehicleFavorite.js";
import Autocomplete from "./js/Autocomplete.js";
import EventBus from "./js/EventBus.js";

console.log("app.js initialisé");

/* ==========================================================
   HELPERS
========================================================== */
function resetInitFlags(root) {
  root.querySelectorAll("[data-initialized='1']").forEach(el => {
    el.dataset.initialized = "0";
  });

  root.querySelectorAll("*").forEach(el => {
    if (el.__fetchFormBound) el.__fetchFormBound = false;
    if (el.__autocompleteBound) el.__autocompleteBound = false;
  });
}

/* ==========================================================
   DROPZONES
========================================================== */
function initDropzones(root = document) {
  root.querySelectorAll(".dropzone").forEach(el => {
    if (el.dataset.initialized === "1") return;

    el.dataset.initialized = "1";
    new Dropzone(el);
  });
}

/* ==========================================================
   FILTRES VEHICULES
========================================================== */
function initFilters(root = document) {
  const form = root.querySelector("#filters-form");
  if (!form) return;

  if (form.dataset.initialized === "1") return;

  form.dataset.initialized = "1";
  new VehiclesFilter(form);
}

/* ==========================================================
   FAVORIS
========================================================== */
function initFavorites(root = document) {
  root.querySelectorAll('[data-action="toggle-favorite"]').forEach(btn => {
    if (btn.dataset.initialized === "1") return;

    btn.dataset.initialized = "1";
    new ToggleVehicleFavorite(btn);
  });
}

/* ==========================================================
   AUTOCOMPLETE
========================================================== */
function initAutocomplete(root = document) {
  root.querySelectorAll("[data-module='autocomplete']").forEach(input => {
    if (input.__autocompleteBound) return;

    input.__autocompleteBound = true;
    new Autocomplete(input);
  });
}

/* ==========================================================
   FETCH FORMS
========================================================== */
function initFetchForms(root = document) {
  root.querySelectorAll("[data-module='fetch-form']").forEach(form => {
    if (form.__fetchFormBound) return;

    form.__fetchFormBound = true;
    new FetchForm(form);
  });
}

/* ==========================================================
   COLLECTIONS
========================================================== */
function initCollections(root = document) {
  root.querySelectorAll("[data-collection]").forEach(el => {
    if (el.dataset.initialized === "1") return;

    el.dataset.initialized = "1";
    new DynamicFormCollection(el);
  });
}

/* ==========================================================
   SLIDERS (CRITIQUE FIX)
========================================================== */
function initSliders(root = document) {
  root.querySelectorAll(".double-slider").forEach(slider => {
    // IMPORTANT: reset après AJAX
    if (slider.dataset.initialized === "1" && !slider.dataset.forceReinit)
      return;

    slider.dataset.initialized = "1";
    delete slider.dataset.forceReinit;

    initDoubleSlider(slider);
  });
}

/* ==========================================================
   AJAX MANAGER
========================================================== */
function initAjaxManager() {
  if (!window.ajaxManager) {
    window.ajaxManager = new AjaxManager();
  }
}

/* ==========================================================
   INIT GLOBAL
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
   UI UPDATE (AJAX)
========================================================== */
EventBus.on("ui:updated", ({ target }) => {
  const root = target || document;

  // RESET STATE BEFORE REINIT
  resetInitFlags(root);

  initAutocomplete(root);
  initFetchForms(root);
  initFavorites(root);
  initCollections(root);
  initSliders(root);

  // ⚠️ IMPORTANT: pas besoin de re-init global
});
