// assets/js/vehiclesFilters.js
console.log("vehiclesFilters.js chargé");

import initDoubleSlider from "./rangeSelector.js";

function initVehicleFilters() {
  const filterForm = document.getElementById("filters-form");
  const resultsContainer = document.getElementById("vehicles-index-results");

  if (!filterForm || !resultsContainer) return;

  const sliderFilters = {};

  // 🔹 Initialise tous les sliders présents dans le DOM
  function initSliders() {
    document.querySelectorAll(".double-slider").forEach(slider => {
      initDoubleSlider(slider);
    });
  }

  initSliders(); // initialisation au chargement

  // 🔹 Debounce pour limiter le nombre de requêtes
  function debounce(fn, delay = 300) {
    let timer;
    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => fn(...args), delay);
    };
  }

  const debouncedSearch = debounce(search, 300);

  // 🔹 Événement custom déclenché par rangeSelector.js
  document.addEventListener("sliderChanged", e => {
    const { filter, min, max } = e.detail;
    sliderFilters[`${filter}Min`] = min;
    sliderFilters[`${filter}Max`] = max;
    debouncedSearch();
  });

  // 🔹 Événement sur le formulaire pour toutes les checkboxes, selects, inputs
  filterForm.addEventListener("change", () => debouncedSearch());

  // 🔹 Fonction de recherche AJAX
  async function search() {
    try {
      const filters = {};

      // ✅ Récupère toutes les checkboxes cochées
      filterForm
        .querySelectorAll("input[type='checkbox']:checked")
        .forEach(input => {
          const key = input.name;
          if (!filters[key]) filters[key] = [];
          filters[key].push(input.value);
        });

      // ✅ Ajoute les valeurs des sliders
      Object.assign(filters, sliderFilters);

      // ✅ Requête AJAX
      const response = await fetch("/vehicles/vehicles-search", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters })
      });

      const html = await response.text();

      // ✅ Injection des résultats
      resultsContainer.innerHTML = html;

      // 🔹 Réinitialise les sliders pour le nouveau DOM
      initSliders();
    } catch (error) {
      console.error("Erreur recherche véhicules :", error);
    }
  }
}

// 🔹 Auto-exécution au chargement de la page
document.addEventListener("DOMContentLoaded", initVehicleFilters);
