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
   * Important : le formulaire est chargé dynamiquement (fragment sidebar),
   * donc il peut ne pas être présent au chargement initial
   */
  let filtersInitialized = false;

  function initFilters() {
    const form = document.getElementById("filters-form");

    // Vérifie que le formulaire existe et n'a pas déjà été initialisé
    if (form && !filtersInitialized) {
      new VehiclesFilter(form); // Correction : on passe l'élément DOM et non une string
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
        observer.disconnect(); // Stoppe l'observation une fois initialisé
        console.log("VehiclesFilter initialisé via observer");
      }
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true
    });
  }

  /**
   * Réinitialisation partielle lors du retour sur l'onglet
   * (utile si contenu AJAX rechargé ou modifié)
   */
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
      // Réinitialisation des FetchForm
      document
        .querySelectorAll("input[data-result-div]")
        .forEach(input => new FetchForm(input));

      // Vérifie si le formulaire de filtres doit être réinitialisé
      initFilters();
    }
  });

  console.log("app.js chargé : modules initialisés");
});
