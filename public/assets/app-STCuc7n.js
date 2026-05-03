/**
 * app.js
 * ------------------------------------------------------------------
 * Initialisation globale M-Motors
 *
 * Objectif :
 * - Initialiser les modules une seule fois proprement
 * - Réinitialiser uniquement après AJAX (ui:updated)
 * - Éviter les conflits DOM / rebinds
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
 * Debug helper
 */
function debug(label, data = null) {
  console.log(`[DEBUG ${label}]`, data ?? "");
}

/**
 * Debug global des inputs (utile temporairement)
 */
document.addEventListener("change", e => {
  const target = e.target;

  if (target instanceof HTMLInputElement) {
    console.log("INPUT CHANGE GLOBAL:", target.name, target.value);
  }
});

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

  debug("Filters form", form);

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
 * IMPORTANT : ne doit JAMAIS être lié à FetchForm
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
 * Fetch forms (AJAX global)
 * IMPORTANT : FetchForm reçoit UNIQUEMENT des FORM
 */
function initFetchForms() {
  document.querySelectorAll("[data-fetch-form]").forEach(form => {
    if (!(form instanceof HTMLFormElement)) {
      console.warn("[FetchForm] invalid element (not a form)", form);
      return;
    }

    if (form.dataset.initialized === "1") return;

    console.log("[FetchForm] init OK", form);

    form.dataset.initialized = "1";

    new FetchForm(form);
  });
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
 * Ajax manager singleton
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
 * Initialisation globale
 */
function initApp() {
  debug("INIT APP START");

  initSidebar?.();
  initDropzones();
  initFilters();
  initFavorites();
  initAutocomplete();
  initFetchForms();
  initCollections();
  initAjaxManager();
  initSliders();
  initSwitcher();

  debug("INIT APP END");
}

/**
 * DOM ready
 */
document.addEventListener("DOMContentLoaded", initApp);

/**
 * Re-init après AJAX global
 */
window.addEventListener("ui:updated", () => {
  debug("UI UPDATED");

  document.querySelectorAll("[data-initialized]").forEach(el => {
    el.removeAttribute("data-initialized");
  });

  initApp();
});
