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
  if (!filterForm) return;

  let sliderFilters = {};

  // Initialisation des sliders
  document.querySelectorAll(".double-slider").forEach(slider => {
    initDoubleSlider(slider);
  });

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
      filterForm
        .querySelectorAll("input[type='checkbox']:checked")
        .forEach(input => {
          const key = input.name;
          if (!filters[key]) filters[key] = [];
          filters[key].push(input.value);
        });

      // Ajout des valeurs des sliders
      Object.assign(filters, sliderFilters);

      // Envoi au controller pour récupérer le fragment Twig
      const response = await fetch("/vehicles/vehicles-search", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters })
      });

      const html = await response.text();

      // Injection directe du fragment Twig dans le tbody
      const resultDiv = document.getElementById("vehicle-results");
      if (resultDiv) {
        resultDiv.innerHTML = html;
      }

      console.log("Filtres appliqués :", filters);
    } catch (error) {
      console.error("Erreur lors de la recherche :", error);
    }
  }
});
