document.addEventListener("DOMContentLoaded", () => {
  const filterForm = document.getElementById("filters-form");
  const formData = new FormData();

  filterForm.addEventListener("change", event => {
    const target = event.target;

    if (target.matches('input[type="checkbox"]')) {
      // On supprime toutes les anciennes valeurs pour ce filtre
      formData.delete(target.name);

      // On récupère toutes les cases cochées du même groupe
      const checked = filterForm.querySelectorAll(
        `input[name="${target.name}"]:checked`
      );

      // On ajoute les valeurs proprement (SANS [])
      checked.forEach(cb => {
        formData.append(target.name, cb.value);
      });

      console.log("--- FormData ---");
      formData.forEach((value, key) => console.log(key, value));
    }

    search();
  });

  const search = async () => {
    try {
      // Convertir FormData en objet JSON propre
      const filters = {};
      formData.forEach((value, key) => {
        if (!filters[key]) {
          filters[key] = [];
        }
        filters[key].push(value);
      });

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
