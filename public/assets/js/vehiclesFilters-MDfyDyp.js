// vehiclesFilters.js
import initDoubleSlider from "./rangeSelector.js";

// Debounce pour éviter les requêtes en rafale
function debounce(fn, delay = 300) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

document.addEventListener("DOMContentLoaded", () => {
  const filterForm = document.getElementById("filters-form");

  // Initialisation des sliders
  document.querySelectorAll(".double-slider").forEach(slider => {
    initDoubleSlider(slider);
  });

  let sliderFilters = {};

  // Sliders → mise à jour des filtres
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

  const debouncedSearch = debounce(search, 300);

  async function search() {
    try {
      const filters = {};

      // Récupération des cases cochées
      const checkedInputs = filterForm.querySelectorAll(
        "input[type='checkbox']:checked"
      );

      checkedInputs.forEach(input => {
        const key = input.name;

        if (!filters[key]) {
          filters[key] = [];
        }

        filters[key].push(input.value);
      });

      // Ajout des valeurs des sliders
      Object.assign(filters, sliderFilters);

      console.log("Filters envoyés :", filters);

      const response = await fetch("/vehicles/vehicles-search", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({ filters })
      });

      const data = await response.json();
      console.log("Résultats filtrés :", data);
    } catch (error) {
      console.error("Erreur :", error);
    }
  }
});
