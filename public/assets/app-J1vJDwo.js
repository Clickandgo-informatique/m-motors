// assets/app.js

// Import des modules principaux
import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";
import "./js/sidebar.js";
import "./js/rangeSelector.js";

// Import des modules applicatifs
import VehiclesFilter from "./js/vehiclesFilters.js";
import DynamicFormCollection from "./js/DynamicFormCollection.js";
import FetchForm from "./js/FetchForm.js";
import AjaxManager from "./js/AjaxManager.js";
import ToggleVehicleFavorite from "./js/ToggleVehicleFavorite.js";

document.addEventListener("DOMContentLoaded", () => {
  /**
   * Initialisation des collections dynamiques (formulaires Symfony)
   */
  document
    .querySelectorAll("[data-collection]")
    .forEach(root => new DynamicFormCollection(root));

  /**
   * Initialisation des formulaires AJAX de type FetchForm
   */
  document
    .querySelectorAll("input[data-result-div]")
    .forEach(input => new FetchForm(input));

  /**
   * Initialisation du gestionnaire AJAX global
   */
  new AjaxManager();

  /**
   * Initialisation du module VehiclesFilter
   * Le formulaire peut être injecté dynamiquement (sidebar AJAX),
   * donc on prévoit une initialisation différée via MutationObserver
   */
  let filtersInitialized = false;

  function initFilters() {
    const form = document.getElementById("filters-form");

    // Vérifie que le formulaire existe et n'a pas déjà été initialisé
    if (form && !filtersInitialized) {
      new VehiclesFilter(form);
      filtersInitialized = true;
      console.log("VehiclesFilter initialisé");
    }
  }

  // Tentative d'initialisation immédiate
  initFilters();

  /**
   * Si le formulaire n'est pas encore présent (chargement AJAX),
   * on observe le DOM pour détecter son apparition
   */
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

    observer.observe(document.body, {
      childList: true,
      subtree: true
    });
  }

  /**
   * Initialisation des favoris
   * Les boutons peuvent être injectés dynamiquement (liste véhicules, filtres, pagination AJAX)
   * On initialise chaque bouton individuellement et on évite les doubles bindings
   */
  function initFavorites() {
    document
      .querySelectorAll('[data-action="toggle-favorite"]')
      .forEach(button => {
        // Empêche de binder plusieurs fois le même bouton
        if (!button.dataset.favoriteInitialized) {
          new ToggleVehicleFavorite(button);
          button.dataset.favoriteInitialized = "true";
        }
      });
  }

  // Initialisation immédiate des favoris
  initFavorites();

  /**
   * Observer global pour gérer les contenus injectés dynamiquement
   * (ex: résultats filtrés, pagination AJAX, reload partiel du DOM)
   */
  const observerFavorites = new MutationObserver(() => {
    initFavorites();
  });

  observerFavorites.observe(document.body, {
    childList: true,
    subtree: true
  });

  /**
   * Réinitialisation partielle lors du retour sur l'onglet
   * Utile si le contenu a changé en arrière-plan
   */
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
      // Réinitialisation des FetchForm
      document
        .querySelectorAll("input[data-result-div]")
        .forEach(input => new FetchForm(input));

      // Vérifie si le formulaire de filtres doit être réinitialisé
      initFilters();

      // Réinitialisation des favoris (sécurité)
      initFavorites();
    }
  });

  console.log("app.js chargé : modules initialisés");
});
