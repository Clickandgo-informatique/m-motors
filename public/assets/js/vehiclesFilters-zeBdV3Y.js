import initDoubleSlider from "./rangeSelector.js";

function initVehicleFilters() {
  const filterForm = document.getElementById("filters-form");
  const resultsContainer = document.getElementById("vehicles-index-results");
  if (!filterForm || !resultsContainer) return;

  const sliderFilters = {};

  function initSliders() {
    document.querySelectorAll(".double-slider").forEach(slider => {
      initDoubleSlider(slider);
    });
  }

  initSliders();

  function debounce(fn, delay = 300) {
    let timer;
    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => fn(...args), delay);
    };
  }

  const debouncedSearch = debounce(search, 300);

  document.addEventListener("sliderChanged", e => {
    const { filter, min, max } = e.detail;
    sliderFilters[`${filter}Min`] = min;
    sliderFilters[`${filter}Max`] = max;
    debouncedSearch();
  });

  filterForm.addEventListener("change", () => debouncedSearch());

  async function search() {
    try {
      const filters = {};
      filterForm
        .querySelectorAll("input[type='checkbox']:checked")
        .forEach(input => {
          const key = input.name;
          if (!filters[key]) filters[key] = [];
          filters[key].push(input.value);
        });

      Object.assign(filters, sliderFilters);

      const response = await fetch("/vehicles/vehicles-search", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filters })
      });

      const html = await response.text();
      resultsContainer.innerHTML = html;

      initSliders();
    } catch (error) {
      console.error("Erreur recherche véhicules :", error);
    }
  }
}

// Auto-exécution
document.addEventListener("DOMContentLoaded", initVehicleFilters);
