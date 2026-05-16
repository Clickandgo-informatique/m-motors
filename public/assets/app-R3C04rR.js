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

function initDropzones(root = document) {
  root.querySelectorAll(".dropzone").forEach(el => {
    if (el.dataset.dropzoneInitialized === "1") return;

    el.dataset.dropzoneInitialized = "1";
    new Dropzone(el);
  });
}

function initFavorites(root = document) {
  root.querySelectorAll('[data-action="toggle-favorite"]').forEach(btn => {
    if (btn.dataset.favoriteInitialized === "1") return;

    btn.dataset.favoriteInitialized = "1";
    new ToggleVehicleFavorite(btn);
  });
}

function initAutocomplete(root = document) {
  root.querySelectorAll("[data-module='autocomplete']").forEach(input => {
    if (input.dataset.autocompleteInitialized === "1") return;

    input.dataset.autocompleteInitialized = "1";
    new Autocomplete(input);
  });
}

function initFetchForms(root = document) {
  root.querySelectorAll("[data-module='fetch-form']").forEach(form => {
    if (form.dataset.fetchFormInitialized === "1") return;

    form.dataset.fetchFormInitialized = "1";
    new FetchForm(form);
  });
}

function initCollections(root = document) {
  root.querySelectorAll("[data-collection]").forEach(el => {
    if (el.dataset.collectionInitialized === "1") return;

    el.dataset.collectionInitialized = "1";
    new DynamicFormCollection(el);
  });
}

function initSliders(root = document) {
  root.querySelectorAll(".double-slider").forEach(slider => {
    if (slider.dataset.sliderInitialized === "1") return;

    slider.dataset.sliderInitialized = "1";

    initDoubleSlider(slider);
  });
}

function initBadges(root = document) {
  const container = root.querySelector("#filters-summary");
  const form = root.querySelector("#filters-form");

  if (!container || !form) return;

  if (container.dataset.badgesInitialized === "1") return;
  container.dataset.badgesInitialized = "1";

  const badges = new FilterBadges(container, form, () => {
    form.dispatchEvent(new Event("change", { bubbles: true }));
  });

  badges.updateBadges();

  window.__filterBadges = badges;
}

function initAjaxManager() {
  if (!window.ajaxManager) {
    window.ajaxManager = new AjaxManager();
  }
}

function initPagination() {
  document.addEventListener("click", e => {
    const link = e.target.closest("[data-page]");
    if (!link) return;

    e.preventDefault();

    const form = document.querySelector("#filters-form");
    if (!form) return;

    let input = form.querySelector("[name='page']");

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

function initApp() {
  initSidebar?.();

  initDropzones();
  initFavorites();
  initAutocomplete();
  initFetchForms();
  initCollections();
  initAjaxManager();
  initSliders();
  initPagination();
  initBadges();
}

document.addEventListener("DOMContentLoaded", initApp);

EventBus.on("ui:updated", ({ target }) => {
  const root = target || document;

  resetInitFlags(root);

  initAutocomplete(root);
  initFetchForms(root);
  initFavorites(root);
  initCollections(root);
  initSliders(root);
  initBadges(root);
});

document.addEventListener("change", e => {
  if (e.target.closest("#filters-form")) {
    console.log("[DEBUG] CHANGE EVENT:", e.target);
    console.trace();
  }
});
