// vehiclesFilters.js
import initDoubleSlider from "./rangeSelector.js";

/**
 * Initialise les filtres véhicules
 */
export function initVehicleFilters() {
  const filterForm = document.getElementById("filters-form");
  if (!filterForm) return;

  // --- INITIALISATION DES SLIDERS ---
  document.querySelectorAll(".double-slider").forEach(slider => {
    initDoubleSlider(slider);
  });

  let sliderFilters = {};
  const resultsDiv = document.getElementById("vehicle-results");

  // --- DEBOUNCE ---
  const debouncedSearch = debounce(search, 300);

  // --- ÉVÉNEMENT SLIDER ---
  document.addEventListener("sliderChanged", e => {
    const { filter, min, max } = e.detail;
    sliderFilters[`${filter}Min`] = min;
    sliderFilters[`${filter}Max`] = max;
    debouncedSearch();
  });

  // --- ÉVÉNEMENT CHECKBOX ---
  filterForm.addEventListener("change", () => {
    debouncedSearch();
  });

  // --- FONCTION DE RECHERCHE AJAX ---
  async function search() {
    try {
      const filters = {};

      // Récupération des checkboxes cochées
      filterForm
        .querySelectorAll("input[type='checkbox']:checked")
        .forEach(input => {
          const key = input.name;
          if (!filters[key]) filters[key] = [];
          filters[key].push(input.value);
        });

      // Ajout des sliders
      Object.assign(filters, sliderFilters);

      // Requête AJAX
      const response = await fetch("/vehicles/vehicles-search", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters })
      });

      const html = await response.text();

      // Injection du fragment HTML
      if (resultsDiv) resultsDiv.innerHTML = html;

      // Ré-attacher les sliders au fragment si nécessaire
      document.querySelectorAll(".double-slider").forEach(slider => {
        initDoubleSlider(slider);
      });
    } catch (err) {
      console.error("Erreur AJAX filters:", err);
    }
  }

  // --- DEBOUNCE UTILITY ---
  function debounce(fn, delay = 300) {
    let timer;
    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => fn(...args), delay);
    };
  }
}
