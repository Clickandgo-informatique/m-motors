// assets/app.js

import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";
import "./js/sidebar.js";
import "./js/rangeSelector.js";

import VehiclesFilter from "./js/vehiclesFilters.js";
import DynamicFormCollection from "./js/DynamicFormCollection.js";
import FetchForm from "./js/FetchForm.js";
import AjaxManager from "./js/AjaxManager.js";
import ToggleVehicleFavorite from "./js/ToggleVehicleFavorite.js";
import Autocomplete from "./js/Autocomplete.js";

document.addEventListener("DOMContentLoaded", () => {
  // -------------------------------
  // Collections dynamiques (forms Symfony)
  // -------------------------------
  document.querySelectorAll("[data-collection]").forEach(root => {
    new DynamicFormCollection(root);
  });

  // -------------------------------
  // Formulaires AJAX FetchForm
  // -------------------------------
  document.querySelectorAll("input[data-result-div]").forEach(input => {
    if (!input.dataset.fetchFormInitialized) {
      new FetchForm(input);
      input.dataset.fetchFormInitialized = "true";
    }
  });

  // -------------------------------
  // Gestionnaire AJAX global (MODALE)
  // -------------------------------
  window.AjaxManagerInstance = new AjaxManager();

  // -------------------------------
  // Initialisation VehiclesFilter
  // -------------------------------
  const filtersForms = new Set();

  function initFilters() {
    const form = document.getElementById("filters-form");
    if (form && !filtersForms.has(form)) {
      new VehiclesFilter(form);
      filtersForms.add(form);
      console.log("VehiclesFilter initialisé");
    }
  }

  initFilters();
  const filtersObserver = new MutationObserver(() => initFilters());
  filtersObserver.observe(document.body, { childList: true, subtree: true });

  // -------------------------------
  // Toggle favoris
  // -------------------------------
  const favsInitialized = new WeakSet();
  function initFavorites() {
    document
      .querySelectorAll('[data-action="toggle-favorite"]')
      .forEach(btn => {
        if (!favsInitialized.has(btn)) {
          new ToggleVehicleFavorite(btn);
          favsInitialized.add(btn);
        }
      });
  }
  initFavorites();
  const favoritesObserver = new MutationObserver(() => initFavorites());
  favoritesObserver.observe(document.body, { childList: true, subtree: true });

  // -------------------------------
  // Autocomplete
  // -------------------------------
  const autocompleteInitialized = new WeakSet();
  function initAutocomplete() {
    document.querySelectorAll('[data-autocomplete="true"]').forEach(input => {
      if (!autocompleteInitialized.has(input)) {
        new Autocomplete(input);
        autocompleteInitialized.add(input);
      }
    });
  }
  initAutocomplete();
  const autocompleteObserver = new MutationObserver(() => initAutocomplete());
  autocompleteObserver.observe(document.body, {
    childList: true,
    subtree: true
  });

  // -------------------------------
  // Réinitialisation au retour sur l'onglet
  // -------------------------------
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
      document.querySelectorAll("input[data-result-div]").forEach(input => {
        if (!input.dataset.fetchFormInitialized) {
          new FetchForm(input);
          input.dataset.fetchFormInitialized = "true";
        }
      });
      initFilters();
      initFavorites();
      initAutocomplete();
    }
  });

  console.log("app.js chargé : modules initialisés");
});
