import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";

// Gestion des formulaires dynamiques
import DynamicFormCollection from "./js/DynamicFormCollection.js";
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("[data-collection]").forEach(root => {
    new DynamicFormCollection(root);
  });
});

// Recherche dans les formulaires
import FetchForm from "./js/FetchForm.js";

/**
 * Initialise tous les champs utilisant FetchForm
 * On isole ça dans une fonction pour pouvoir la rappeler
 * quand la page revient du cache (bfcache).
 */
function initFetchForms() {
  // Sélectionne tous les inputs ayant data-search-form
  // (c’est ton attribut qui indique qu’un champ utilise FetchForm)
  document.querySelectorAll("[data-search-form]").forEach(input => {
    // Instancie ton composant JS
    // → attache les listeners
    // → active l’autocomplete
    // → active le scroll infini
    new FetchForm(input);
  });
}

/**
 * Exécution normale au chargement de la page.
 * DOMContentLoaded = la page vient d’être chargée "pour de vrai".
 */
document.addEventListener("DOMContentLoaded", initFetchForms);

/**
 * Gestion du Back/Forward Cache (bfcache)
 *
 * Quand l’utilisateur ouvre une fiche véhicule puis revient en arrière :
 * - la page n’est PAS rechargée
 * - le JS n’est PAS ré‑exécuté
 * - les listeners sont perdus
 *
 * L’événement `pageshow` se déclenche même si la page vient du cache.
 * `event.persisted === true` signifie : "la page vient du bfcache".
 */
window.addEventListener("pageshow", event => {
  // Si la page revient du cache → réinitialiser FetchForm
  if (event.persisted) {
    initFetchForms();
  }
});

console.log("This log comes from assets/app.js - welcome to AssetMapper! 🎉");
