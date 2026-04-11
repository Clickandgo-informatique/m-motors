// assets/app.js

// -----------------------------
// Import des modules principaux
// -----------------------------
import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";
import "./js/sidebar.js";
import "./js/rangeSelector.js";

// -----------------------------
// Import des modules applicatifs
// -----------------------------
import VehiclesFilter from "./js/vehiclesFilters.js";
import DynamicFormCollection from "./js/DynamicFormCollection.js";
import FetchForm from "./js/FetchForm.js";
import AjaxManager from "./js/AjaxManager.js";
import ToggleVehicleFavorite from "./js/ToggleVehicleFavorite.js";
import Autocomplete from "./js/Autocomplete.js";

document.addEventListener("DOMContentLoaded", () => {
  // -----------------------------
  // Initialisation des collections dynamiques (formulaires Symfony)
  // -----------------------------
  document.querySelectorAll("[data-collection]").forEach(root => {
    new DynamicFormCollection(root);
  });

  // -----------------------------
  // Initialisation des formulaires AJAX de type FetchForm
  // -----------------------------
  document.querySelectorAll("input[data-result-div]").forEach(input => {
    new FetchForm(input);
  });

  // -----------------------------
  // Initialisation du gestionnaire AJAX global
  // -----------------------------
  new AjaxManager();

  // -----------------------------
  // Initialisation du module VehiclesFilter
  // -----------------------------
  let filtersInitialized = false;

  function initFilters() {
    const form = document.getElementById("filters-form");

    if (form && !filtersInitialized) {
      new VehiclesFilter(form);
      filtersInitialized = true;
      console.log("VehiclesFilter initialisé");
    }
  }

  // Tentative d'initialisation immédiate
  initFilters();

  // Observer si le formulaire arrive dynamiquement
  if (!filtersInitialized) {
    const observer = new MutationObserver(() => {
      const form = document.getElementById("filters-form");
      if (form && !filtersInitialized) {
        new VehiclesFilter(form);
        filtersInitialized = true;
        observer.disconnect();
        console.log("VehiclesFilter initialisé via observer");
      }
    });

    observer.observe(document.body, { childList: true, subtree: true });
  }

  // -----------------------------
  // Initialisation des favoris
  // -----------------------------
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

  // -----------------------------
  // Initialisation autocomplete (indépendant de FetchForm)
  // -----------------------------
  function initAutocomplete() {
    document.querySelectorAll("[data-autocomplete]").forEach(input => {
      if (!input.dataset.autocompleteInitialized) {
        new Autocomplete(input);
        input.dataset.autocompleteInitialized = "true";
      }
    });
  }

  // Init immédiat
  initAutocomplete();

  // -----------------------------
  // Observer global pour contenus injectés dynamiquement
  // -----------------------------
  const observerDynamic = new MutationObserver(() => {
    initFavorites();
    initAutocomplete();
    initFilters();
  });

  observerDynamic.observe(document.body, { childList: true, subtree: true });

  // -----------------------------
  // Réinitialisation partielle lors du retour sur l'onglet
  // -----------------------------
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
      document.querySelectorAll("input[data-result-div]").forEach(input => {
        new FetchForm(input);
      });
      initFilters();
      initFavorites();
      initAutocomplete();
    }
  });

  console.log("app.js chargé : modules initialisés");
});
