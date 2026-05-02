/**
 * app.js
 * ------------------------------------------------------------------
 * Point d'entrée principal de l'application M-Motors
 *
 * Responsabilités :
 * - Initialisation des modules JS
 * - Gestion des re-initialisations après AJAX (ui:updated)
 * - Protection contre les double bindings
 *
 * IMPORTANT :
 * On évite tout système de "appInitialized global"
 * qui empêche les rebinds après update DOM.
 * ------------------------------------------------------------------
 */
console.log("APP.JS LOADED");

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
 * Filtres véhicules
 */
function initFilters() {
  const form = document.getElementById("filters-form");
  if (!form) return;

  if (form.dataset.initialized === "1") return;

  form.dataset.initialized = "1";
  new VehiclesFilter(form);
}

/**
 * Favoris véhicules
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
  document.querySelectorAll('[data-autocomplete="true"]').forEach(input => {
    if (!(input instanceof HTMLInputElement)) return;
    if (input.dataset.initialized === "1") return;

    input.dataset.initialized = "1";
    new Autocomplete(input);
  });
}

/**
 * Fetch forms AJAX (filtres, toggle view, search, etc.)
 */
function initFetchForms() {
  document.querySelectorAll("[data-fetch-form]").forEach(form => {
    if (form.dataset.initialized === "1") return;

    form.dataset.initialized = "1";
    new FetchForm(form);
  });
}

/**
 * Collections dynamiques de formulaires
 */
function initCollections() {
  document.querySelectorAll("[data-collection]").forEach(root => {
    if (root.dataset.initialized === "1") return;

    root.dataset.initialized = "1";
    new DynamicFormCollection(root);
  });
}

/**
 * AjaxManager global (singleton)
 */
function initAjaxManager() {
  if (!window.AjaxManagerInstance) {
    window.AjaxManagerInstance = new AjaxManager();
  }
}

/**
 * Sliders double range
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
 * Initialisation globale de l'application
 *
 * IMPORTANT :
 * - Appelée au chargement initial
 * - Reappelée après ui:updated
 * - Les modules doivent être idempotents
 */
function initApp() {
  console.log("INIT APP EXEC");
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
 *
 * IMPORTANT :
 * - On reset uniquement les flags data-initialized
 * - Puis on relance initApp proprement
 */
window.addEventListener("ui:updated", () => {
  document.querySelectorAll("[data-initialized]").forEach(el => {
    el.removeAttribute("data-initialized");
  });

  initApp();
});
