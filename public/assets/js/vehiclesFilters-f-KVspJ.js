// vehiclesFilters.js
import initDoubleSlider from "./rangeSelector.js";

/**
 * Initialise les filtres véhicules sur la page d'index
 */
export function initVehicleFilters() {
  const filterForm = document.getElementById("filters-form");
  const resultsContainer = document.getElementById("vehicles-index-results");

  // On quitte si on n'est pas sur la page index ou si le container manque
  if (!filterForm || !resultsContainer) return;

  // Initialisation des sliders
  const sliderFilters = {};
  document.querySelectorAll(".double-slider").forEach(slider => {
    initDoubleSlider(slider);
  });

  // Debounce pour éviter trop de requêtes
  function debounce(fn, delay = 300) {
    let timer;
    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => fn(...args), delay);
    };
  }

  const debouncedSearch = debounce(search, 300);

  // Événement sur sliders (custom event "sliderChanged" déclenché par rangeSelector.js)
  document.addEventListener("sliderChanged", e => {
    const { filter, min, max } = e.detail;
    sliderFilters[`${filter}Min`] = min;
    sliderFilters[`${filter}Max`] = max;
    debouncedSearch();
  });

  // Événement sur les checkboxes
  filterForm.addEventListener("change", () => {
    debouncedSearch();
  });

  async function search() {
    try {
      const filters = {};

      // Récupération des cases cochées
      filterForm
        .querySelectorAll("input[type='checkbox']:checked")
        .forEach(input => {
          const key = input.name;
          if (!filters[key]) filters[key] = [];
          filters[key].push(input.value);
        });

      // Ajout des sliders
      Object.assign(filters, sliderFilters);

      // Requête AJAX vers le controller
      const response = await fetch("/vehicles/vehicles-search", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters })
      });

      // Injection uniquement dans le conteneur de la page index
      const html = await response.text();
      resultsContainer.innerHTML = html;

      // Optionnel : réinitialiser les scripts JS sur les nouveaux contenus si nécessaire
      // initFetchForms();  // uniquement si tu veux activer autocomplete dans les résultats
    } catch (error) {
      console.error("Erreur recherche véhicules :", error);
    }
  }
}
