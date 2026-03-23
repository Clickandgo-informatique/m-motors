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

document.addEventListener("DOMContentLoaded", () => {
  // DynamicFormCollection
  document
    .querySelectorAll("[data-collection]")
    .forEach(root => new DynamicFormCollection(root));

  // FetchForm
  document
    .querySelectorAll("input[data-result-div]")
    .forEach(input => new FetchForm(input));

  // AjaxManager
  new AjaxManager();

  // VehiclesFilter avec guard et MutationObserver
  let filtersInitialized = false;
  function initFilters() {
    const form = document.getElementById("filters-form");
    if (form && !filtersInitialized) {
      new VehiclesFilter("#filters-form");
      filtersInitialized = true;
    }
  }
  initFilters();

  if (!filtersInitialized) {
    const observer = new MutationObserver(() => {
      const form = document.getElementById("filters-form");
      if (form && !filtersInitialized) {
        new VehiclesFilter("#filters-form");
        filtersInitialized = true;
        observer.disconnect();
      }
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }

  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
      document
        .querySelectorAll("input[data-result-div]")
        .forEach(input => new FetchForm(input));
      initFilters();
    }
  });

  console.log(
    "app.js chargé : DynamicFormCollection, FetchForm, AjaxManager, VehiclesFilter initialisés"
  );
});
