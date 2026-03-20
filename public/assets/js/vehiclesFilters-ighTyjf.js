// assets/js/vehiclesFilters.js
import initDoubleSlider from "./rangeSelector.js";

export function initVehicleFilters() {
  const filterForm = document.getElementById("filters-form");
  const resultsContainer = document.getElementById("vehicles-index-results");

  if (!filterForm || !resultsContainer) {
    console.log(
      "initVehicleFilters: #filters-form ou #vehicles-index-results introuvable, attente du DOM..."
    );
    return;
  }

  const sliderFilters = {};

  // 🔹 Initialisation des sliders présents dans le DOM
  function initSliders() {
    const sliders = document.querySelectorAll(".double-slider");
    if (!sliders.length) return;
    sliders.forEach(slider => {
      initDoubleSlider(slider);
    });
    console.log("Sliders initialisés:", sliders.length);
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

  // 🔹 Événement sur le formulaire pour checkboxes / selects / inputs
  filterForm.addEventListener("change", () => debouncedSearch());

  // 🔹 Fonction de recherche AJAX
  async function search() {
    try {
      const filters = {};

      // ✅ Récupération des checkboxes cochées
      filterForm
        .querySelectorAll("input[type='checkbox']:checked")
        .forEach(input => {
          const key = input.name;
          if (!filters[key]) filters[key] = [];
          filters[key].push(input.value);
        });

      // ✅ Ajout des valeurs des sliders
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

      // 🔹 Réinitialisation des sliders après injection HTML
      initSliders();

      console.log("Résultats mis à jour via AJAX");
    } catch (error) {
      console.error("Erreur recherche véhicules :", error);
    }
  }

  console.log("initVehicleFilters prêt ✅");
}
