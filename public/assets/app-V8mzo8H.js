/**
 * app.js
 * ------------------------------------------------------------------
 * Point d'entrée principal de l'application M-Motors
 *
 * Version DEBUG :
 * - Logs détaillés pour chaque module
 * - Vérification des datasets requis
 * - Traçabilité complète de l'initialisation
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
 * Logger de debug centralisé
 */
function debug(label, data = null) {
  console.log(`[DEBUG ${label}]`, data ?? "");
}

/**
 * Wrapper de sécurité
 */
function safeInit(fn, label = "module") {
  try {
    fn();
  } catch (e) {
    console.error(`[ERROR ${label}]`, e);
  }
}

/**
 * Dropzones
 */
function initDropzones() {
  const elements = document.querySelectorAll(".dropzone");

  debug("Dropzones found", elements.length);

  elements.forEach(el => {
    if (el.dataset.initialized === "1") {
      debug("Dropzone already initialized");
      return;
    }

    el.dataset.initialized = "1";

    try {
      new Dropzone(el);
      debug("Dropzone init OK");
    } catch (e) {
      console.error("[ERROR Dropzone]", e);
    }
  });
}

/**
 * Filtres véhicules
 */
function initFilters() {
  const form = document.getElementById("filters-form");

  debug("Filters form", form);

  if (!form) {
    console.warn("[WARN Filters] form not found");
    return;
  }

  if (!form.dataset.fetchUrl && !form.action) {
    console.error("[ERROR Filters] missing fetchUrl/action");
  }

  if (!form.dataset.target) {
    console.warn("[WARN Filters] missing data-target");
  }

  if (form.dataset.initialized === "1") {
    debug("Filters already initialized");
    return;
  }

  form.dataset.initialized = "1";

  try {
    new VehiclesFilter(form);
    debug("Filters init OK");
  } catch (e) {
    console.error("[ERROR Filters]", e);
  }
}

/**
 * Favoris véhicules
 */
function initFavorites() {
  const buttons = document.querySelectorAll('[data-action="toggle-favorite"]');

  debug("Favorites buttons found", buttons.length);

  buttons.forEach(btn => {
    if (btn.dataset.initialized === "1") return;

    btn.dataset.initialized = "1";

    try {
      new ToggleVehicleFavorite(btn);
      debug("Favorite init OK");
    } catch (e) {
      console.error("[ERROR Favorites]", e);
    }
  });
}

/**
 * Autocomplete
 */
function initAutocomplete() {
  const inputs = document.querySelectorAll('[data-autocomplete="true"]');

  debug("Autocomplete inputs found", inputs.length);

  inputs.forEach(input => {
    if (!(input instanceof HTMLInputElement)) {
      console.warn("[WARN Autocomplete] invalid element", input);
      return;
    }

    if (input.dataset.initialized === "1") {
      debug("Autocomplete already initialized");
      return;
    }

    input.dataset.initialized = "1";

    try {
      new Autocomplete(input);
      debug("Autocomplete init OK");
    } catch (e) {
      console.error("[ERROR Autocomplete]", e);
    }
  });
}

/**
 * Fetch forms AJAX (toggle, filtres, search)
 */
function initFetchForms() {
  const forms = document.querySelectorAll("[data-fetch-form]");

  debug("FetchForms found", forms.length);

  forms.forEach(form => {
    debug("FetchForm candidate", form);

    if (form.dataset.initialized === "1") {
      debug("FetchForm already initialized");
      return;
    }

    if (!form.dataset.fetchUrl && !form.action) {
      console.error("[ERROR FetchForm] missing URL", form);
    }

    if (!form.dataset.target) {
      console.warn("[WARN FetchForm] missing data-target", form);
    }

    form.dataset.initialized = "1";

    try {
      new FetchForm(form);
      debug("FetchForm init OK");
    } catch (e) {
      console.error("[ERROR FetchForm]", e);
    }
  });
}

/**
 * Collections dynamiques
 */
function initCollections() {
  const roots = document.querySelectorAll("[data-collection]");

  debug("Collections found", roots.length);

  roots.forEach(root => {
    if (root.dataset.initialized === "1") return;

    root.dataset.initialized = "1";

    try {
      new DynamicFormCollection(root);
      debug("Collection init OK");
    } catch (e) {
      console.error("[ERROR Collection]", e);
    }
  });
}

/**
 * AjaxManager global
 */
function initAjaxManager() {
  if (!window.AjaxManagerInstance) {
    window.AjaxManagerInstance = new AjaxManager();
    debug("AjaxManager initialized");
  } else {
    debug("AjaxManager already exists");
  }
}

/**
 * Sliders
 */
function initSliders() {
  const sliders = document.querySelectorAll(".double-slider");

  debug("Sliders found", sliders.length);

  sliders.forEach(slider => {
    if (slider.dataset.initialized === "1") return;

    slider.dataset.initialized = "1";

    try {
      initDoubleSlider(slider);
      debug("Slider init OK");
    } catch (e) {
      console.error("[ERROR Slider]", e);
    }
  });
}

/**
 * Initialisation globale
 */
function initApp() {
  debug("INIT APP START");

  safeInit(initSidebar, "sidebar");
  safeInit(initDropzones, "dropzones");
  safeInit(initFilters, "filters");
  safeInit(initFavorites, "favorites");
  safeInit(initAutocomplete, "autocomplete");
  safeInit(initFetchForms, "fetchForms");
  safeInit(initCollections, "collections");
  safeInit(initAjaxManager, "ajaxManager");
  safeInit(initSliders, "sliders");

  debug("INIT APP END");
}

/**
 * DOM ready
 */
document.addEventListener("DOMContentLoaded", initApp);

/**
 * Réinitialisation après AJAX global
 */
window.addEventListener("ui:updated", () => {
  debug("UI UPDATED EVENT");

  document.querySelectorAll("[data-initialized]").forEach(el => {
    el.removeAttribute("data-initialized");
  });

  initApp();
});
