document.addEventListener("DOMContentLoaded", () => {
  const filterForm = document.getElementById("filters-form");

  filterForm.addEventListener("change", () => {
    search();
  });

  const search = async () => {
    try {
      const filters = {};

      // Récupère toutes les cases cochées du formulaire
      const checkedInputs = filterForm.querySelectorAll(
        "input[type='checkbox']:checked"
      );

      checkedInputs.forEach(input => {
        const key = input.name; // ex: "brand", "fuelType", "bodyType"

        if (!filters[key]) {
          filters[key] = [];
        }

        filters[key].push(input.value);
      });

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
  };
});
