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
import FilterBadges from "./js/FilterBadges.js";
import initFiltersDrawer from "./js/sidebar.js";
import "./js/cookie-banner.js";

window.Autocomplete = Autocomplete;

console.log("app.js initialisé");

/**
 * Réinitialise les flags d'initialisation pour permettre le re-render après AJAX
 */
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

/**
 * Dropzones
 */
function initDropzones(root = document) {
  root.querySelectorAll(".dropzone").forEach(el => {
    if (el.dataset.dropzoneInitialized === "1") return;

    el.dataset.dropzoneInitialized = "1";
    new Dropzone(el);
  });
}

/**
 * Favoris véhicules
 */
function initFavorites(root = document) {
  root.querySelectorAll('[data-action="toggle-favorite"]').forEach(btn => {
    if (btn.dataset.favoriteInitialized === "1") return;

    btn.dataset.favoriteInitialized = "1";
    new ToggleVehicleFavorite(btn);
  });
}

/**
 * Autocomplete
 */
function initAutocomplete(root = document) {
  root.querySelectorAll("[data-module='autocomplete']").forEach(input => {
    if (input.dataset.autocompleteInitialized === "1") return;

    input.dataset.autocompleteInitialized = "1";
    new Autocomplete(input);
  });
}

/**
 * FetchForm AJAX (filtres + pagination + search)
 */
function initFetchForms(root = document) {
  root.querySelectorAll("[data-module='fetch-form']").forEach(form => {
    if (form.dataset.fetchFormInitialized === "1") return;

    form.dataset.fetchFormInitialized = "1";
    new FetchForm(form);
  });
}

/**
 * Collections dynamiques
 */
function initCollections(root = document) {
  root.querySelectorAll("[data-collection]").forEach(el => {
    if (el.dataset.collectionInitialized === "1") return;

    el.dataset.collectionInitialized = "1";
    new DynamicFormCollection(el);
  });
}

/**
 * Sliders double range
 */
function initSliders(root = document) {
  root.querySelectorAll(".double-slider").forEach(slider => {
    if (slider.dataset.sliderInitialized === "1") return;

    slider.dataset.sliderInitialized = "1";

    initDoubleSlider(slider);
  });
}

/**
 * Badges filtres
 */
function initBadges(root = document) {
  const container = root.querySelector("#filters-summary");
  const form = root.querySelector("#filters-form");

  if (!container || !form) return;

  if (!window.__filterBadges) {
    window.__filterBadges = new FilterBadges(container, form, () => {
      form.dispatchEvent(new Event("change", { bubbles: true }));
    });
  }

  window.__filterBadges.updateBadges();
}

/**
 * Réinitialisation UI globale
 */
function refreshUI(root = document) {
  initBadges(root);
}

/**
 * Ajax manager global (si utilisé ailleurs dans le projet)
 */
function initAjaxManager() {
  if (!window.ajaxManager) {
    window.ajaxManager = new AjaxManager();
  }
}

/**
 * Pagination globale unifiée
 *
 * Important :
 * - un seul listener global
 * - basé uniquement sur data-page
 * - fonctionne après AJAX sans réattachement
 */
function initPagination() {
  document.addEventListener("click", e => {
    const link = e.target.closest("[data-page]");
    if (!link) return;

    e.preventDefault();

    const form = document.querySelector("[data-module='fetch-form']");
    if (!form) return;

    let input = form.querySelector("input[name='page']");

    if (!input) {
      input = document.createElement("input");
      input.type = "hidden";
      input.name = "page";
      form.appendChild(input);
    }

    input.value = link.dataset.page;

    form.dispatchEvent(new Event("change", { bubbles: true }));
  });
}

/**
 * Initialisation globale de l'application
 */
function initApp() {
  initSidebar?.();

  initDropzones();
  initFavorites();
  initAutocomplete();
  initFetchForms();
  initCollections();
  initAjaxManager();
  initSliders();

  // Pagination unique (important : ne pas dupliquer ailleurs)
  // initPagination();

  initBadges();
  initFiltersDrawer();
}

document.addEventListener("DOMContentLoaded", initApp);

/**
 * Réinitialisation après mise à jour AJAX
 */
EventBus.on("ui:updated", ({ target }) => {
  const root = target || document;

  resetInitFlags(root);

  initAutocomplete(root);
  initFetchForms(root);
  initFavorites(root);
  initCollections(root);
  initSliders(root);
  
  refreshUI(root);
  initPagination();
});

/**
 * Debug temporaire des changements de filtres
 */
document.addEventListener("change", e => {
  if (e.target.closest("#filters-form")) {
    console.log("[DEBUG] CHANGE EVENT:", e.target);
    console.trace();
  }
});

/**
 * Debug click global (réduit pour éviter pollution sur pagination)
 * Ne trace que les éléments avec data-id
 */
document.addEventListener("click", e => {
  const el = e.target.closest("[data-id]");
  if (!el) return;

  console.log("CLICK ELEMENT", el);
  console.log("DATA-ID", el.dataset?.id);
});
