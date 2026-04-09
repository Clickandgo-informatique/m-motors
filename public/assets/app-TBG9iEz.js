// assets/app.js

// ==============================
// Import des modules principaux
// ==============================
import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";
import "./js/sidebar.js";
import "./js/rangeSelector.js";

// ==============================
// Import des modules applicatifs
// ==============================
import VehiclesFilter from "./js/vehiclesFilters.js";
import DynamicFormCollection from "./js/DynamicFormCollection.js";
import FetchForm from "./js/FetchForm.js";
import AjaxManager from "./js/AjaxManager.js";
import ToggleVehicleFavorite from "./js/ToggleVehicleFavorite.js";
import Autocomplete from "./js/Autocomplete.js";

document.addEventListener("DOMContentLoaded", () => {
  // -------------------------------------
  // Collections dynamiques (Symfony Forms)
  // -------------------------------------
  document
    .querySelectorAll("[data-collection]")
    .forEach(root => new DynamicFormCollection(root));

  // -------------------------------------
  // Formulaires AJAX de type FetchForm
  // -------------------------------------
  document
    .querySelectorAll("input[data-result-div]")
    .forEach(input => new FetchForm(input));

  // -------------------------------------
  // Gestionnaire AJAX global
  // -------------------------------------
  new AjaxManager();

  // =====================================
  // VEHICLES FILTER (formulaire filtres)
  // =====================================
  let filtersInitialized = false;

  function initFilters() {
    const form = document.getElementById("filters-form");
    if (form && !filtersInitialized) {
      new VehiclesFilter(form);
      filtersInitialized = true;
      console.log("VehiclesFilter initialisé");
    }
  }

  initFilters(); // tentative immédiate

  // =====================================
  // FAVORIS (boutons toggle favorite)
  // =====================================
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

  initFavorites(); // immédiat

  // =====================================
  // AUTOCOMPLETE ROBUSTE
  // =====================================
  function initAutocomplete() {
    document.querySelectorAll("[data-autocomplete]").forEach(input => {
      console.log("Vérification input autocomplete :", input);

      if (!input.dataset.autocompleteInitialized) {
        new Autocomplete(input);
        input.dataset.autocompleteInitialized = "true";
        console.log("Autocomplete instancié :", input);
      }
    });
  }

  initAutocomplete(); // tentative immédiate

  // =====================================
  // OBSERVER GLOBAL pour contenus injectés dynamiquement
  // =====================================
  const observerDynamic = new MutationObserver(mutations => {
    let foundNewAutocomplete = false;

    mutations.forEach(mutation => {
      mutation.addedNodes.forEach(node => {
        if (node.nodeType !== 1) return; // uniquement les éléments HTML

        // Détection de nouveaux inputs autocomplete
        if (node.matches && node.matches("[data-autocomplete]"))
          foundNewAutocomplete = true;
        if (
          node.querySelectorAll &&
          node.querySelectorAll("[data-autocomplete]").length > 0
        )
          foundNewAutocomplete = true;
      });
    });

    // Réinitialisation des modules si de nouveaux éléments détectés
    initFilters();
    initFavorites();
    if (foundNewAutocomplete) initAutocomplete();
  });

  observerDynamic.observe(document.body, {
    childList: true,
    subtree: true
  });

  // =====================================
  // REINITIALISATION lors du retour sur l'onglet
  // =====================================
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
      // Reinitialisation des FetchForm
      document
        .querySelectorAll("input[data-result-div]")
        .forEach(input => new FetchForm(input));

      // Vérifications et réinitialisation des modules
      initFilters();
      initFavorites();
      initAutocomplete();
    }
  });

  console.log("app.js chargé : modules initialisés");
});
