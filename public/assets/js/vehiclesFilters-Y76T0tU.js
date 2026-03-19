import initDoubleSlider from "./rangeSelector.js";

document.addEventListener("DOMContentLoaded", () => {
  // Active tous les sliders
  document.querySelectorAll(".double-slider").forEach(slider => {
    initDoubleSlider(slider); // ✔️ slider est bien défini
  });

  const filterForm = document.getElementById("filters-form");

  let sliderFilters = {}; // stockage des valeurs des sliders

  // Écoute les sliders
  document.addEventListener("sliderChanged", e => {
    const { filter, min, max } = e.detail;

    sliderFilters[filter + "Min"] = min;
    sliderFilters[filter + "Max"] = max;

    search();
  });

  // Écoute les checkboxes
  filterForm.addEventListener("change", () => {
    search();
  });

  // Fonction de recherche globale
  async function search() {
    try {
      const filters = {};

      // Récupère toutes les cases cochées
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

      // Ajoute les valeurs des sliders
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
