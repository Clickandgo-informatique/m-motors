// vehiclesFilters.js
import initDoubleSlider from "./rangeSelector.js";

// Fonction debounce générique
function debounce(fn, delay = 300) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

// Fonction principale d'initialisation des filtres
export function initVehicleFilters() {
  const filterForm = document.getElementById("filters-form");
  if (!filterForm) return; // formulaire non présent → on quitte

  // Initialisation des sliders
  document.querySelectorAll(".double-slider").forEach(slider => {
    initDoubleSlider(slider);
  });

  let sliderFilters = {};
  const debouncedSearch = debounce(search, 300);

  // Événement custom pour sliders
  document.addEventListener("sliderChanged", e => {
    const { filter, min, max } = e.detail;
    sliderFilters[`${filter}Min`] = min;
    sliderFilters[`${filter}Max`] = max;
    debouncedSearch();
  });

  // Checkboxes → mise à jour des filtres
  filterForm.addEventListener("change", () => {
    debouncedSearch();
  });

  async function search() {
    const filters = {};

    // Cases cochées
    filterForm
      .querySelectorAll("input[type='checkbox']:checked")
      .forEach(input => {
        const key = input.name;
        if (!filters[key]) filters[key] = [];
        filters[key].push(input.value);
      });

    // Ajout des sliders
    Object.assign(filters, sliderFilters);

    try {
      const response = await fetch("/vehicles/vehicles-search", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters })
      });

      const data = await response.json();
      console.log("Résultats filtrés :", data);
    } catch (error) {
      console.error("Erreur fetch filters :", error);
    }
  }
}
