import "./stimulus_bootstrap.js";
import "./js/theme.js";
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import "./styles/app.css";
//Gestion des formulaires dynamiques
import DynamicFormCollection from "./js/DynamicFormCollection.js";
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("[data-collection]").forEach(root => {
    new DynamicFormCollection(root);
  });
});

// Recherche dans les formulaires
import UniversalSearch from './js/UniversalSearch.js';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-search').forEach(input => {
        new UniversalSearch(input);
    });
});


console.log("This log comes from assets/app.js - welcome to AssetMapper! 🎉");
