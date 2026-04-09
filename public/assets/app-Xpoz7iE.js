// assets/app.js

// ===============================
// Import des modules principaux
// ===============================
import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";
import "./js/sidebar.js";
import "./js/rangeSelector.js";

// ===============================
// Import des modules applicatifs
// ===============================
import VehiclesFilter from "./js/vehiclesFilters.js";
import DynamicFormCollection from "./js/DynamicFormCollection.js";
import FetchForm from "./js/FetchForm.js";
import AjaxManager from "./js/AjaxManager.js";
import ToggleVehicleFavorite from "./js/ToggleVehicleFavorite.js";
import Autocomplete from "./js/Autocomplete.js";

// ===============================
// DOMContentLoaded : initialisation
// ===============================
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
    new FetchForm(input);
  });

  // -------------------------------
  // Gestionnaire AJAX global
  // -------------------------------
  new AjaxManager();

  // -------------------------------
  // Initialisation VehiclesFilter
  // -------------------------------
  let filtersInitialized = false;

  function initFilters() {
    const form = document.getElementById("filters-form");
    if (form && !filtersInitialized) {
      new VehiclesFilter(form);
      filtersInitialized = true;
      console.log("VehiclesFilter initialisé");
    }
  }

  initFilters();

  // Observer pour init Filters si formulaire injecté dynamiquement
  const filtersObserver = new MutationObserver(() => initFilters());
  filtersObserver.observe(document.body, { childList: true, subtree: true });

  // -------------------------------
  // Initialisation ToggleVehicleFavorite
  // -------------------------------
  function initFavorites() {
    document
      .querySelectorAll('[data-action="toggle-favorite"]')
      .forEach(button => {
        if (!button.dataset.favoriteInitialized) {
          new ToggleVehicleFavorite(button);
          button.dataset.favoriteInitialized = "true";
        }
      });
  }

  initFavorites();

  // Observer global pour les boutons favoris injectés dynamiquement
  const favoritesObserver = new MutationObserver(() => initFavorites());
  favoritesObserver.observe(document.body, { childList: true, subtree: true });

  // -------------------------------
  // Initialisation Autocomplete
  // -------------------------------
  function initAutocomplete() {
    document.querySelectorAll('[data-autocomplete="true"]').forEach(input => {
      if (!input.dataset.autocompleteInitialized) {
        new Autocomplete(input);
        input.dataset.autocompleteInitialized = "true";
      }
    });
  }

  initAutocomplete();

  // Observer global pour inputs autocomplete injectés dynamiquement
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
      // Re-init FetchForm
      document
        .querySelectorAll("input[data-result-div]")
        .forEach(input => new FetchForm(input));

      // Re-init Filters
      initFilters();

      // Re-init Favorites
      initFavorites();

      // Re-init Autocomplete
      initAutocomplete();
    }
  });

  console.log("app.js chargé : modules initialisés");
});
