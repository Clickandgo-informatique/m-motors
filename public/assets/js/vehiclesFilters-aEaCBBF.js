import initDoubleSlider from "./rangeSelector.js";

// Debounce générique
function debounce(fn, delay = 300) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

document.addEventListener("DOMContentLoaded", () => {
  const container = document.getElementById("filters-container");
  if (!container) return; // pas de sidebar sur d'autres pages

  // Charger les filtres via AJAX
  fetch("/vehicles/filters")
    .then(res => res.text())
    .then(html => {
      container.innerHTML = html;
      initFilters(); // initialisation sliders + checkboxes
    })
    .catch(err => console.error("Erreur chargement filtres :", err));
});

function initFilters() {
  const filterForm = document.getElementById("filters-form");
  if (!filterForm) return;

  let sliderFilters = {};

  // Initialisation des sliders
  document.querySelectorAll(".double-slider").forEach(slider => {
    initDoubleSlider(slider);
  });

  // Debounce pour la recherche
  const debouncedSearch = debounce(search, 300);

  // Sliders → mise à jour
  document.addEventListener("sliderChanged", e => {
    const { filter, min, max } = e.detail;
    sliderFilters[`${filter}Min`] = min;
    sliderFilters[`${filter}Max`] = max;
    debouncedSearch();
  });

  // Checkboxes → mise à jour
  filterForm.addEventListener("change", () => {
    debouncedSearch();
  });

  async function search() {
    try {
      const filters = {};

      // Checkboxes
      filterForm
        .querySelectorAll("input[type='checkbox']:checked")
        .forEach(input => {
          if (!filters[input.name]) filters[input.name] = [];
          filters[input.name].push(input.value);
        });

      // Sliders
      Object.assign(filters, sliderFilters);

      console.log("Filters envoyés :", filters);

      const response = await fetch("/vehicles/vehicles-search", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters })
      });

      const data = await response.json();
      console.log("Résultats filtrés :", data);

      // Ici : mise à jour de la liste des véhicules
      const resultsDiv = document.getElementById("vehicle-results");
      if (resultsDiv) {
        resultsDiv.innerHTML = data
          .map(
            v => `
          <div class="vehicle-item">
            ${v.brand} ${v.model} - ${v.registrationNumber} - ${v.mileage} km - ${v.price} €
          </div>
        `
          )
          .join("");
      }
    } catch (err) {
      console.error("Erreur recherche véhicules :", err);
    }
  }
}
