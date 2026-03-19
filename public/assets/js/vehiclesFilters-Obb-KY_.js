document.addEventListener("DOMContentLoaded", () => {
  const filterForm = document.getElementById("filters-form");
  const formData = new FormData();

  filterForm.addEventListener("change", event => {
    const target = event.target;

    if (target.matches('input[type="checkbox"]')) {
      formData.delete(target.name + "[]");

      const checked = filterForm.querySelectorAll(
        `input[name="${target.name}"]:checked`
      );

      checked.forEach(cb => {
        formData.append(cb.name + "[]", cb.value);
      });

      console.log("--- FormData ---");
      formData.forEach((value, key) => console.log(key, value));
    }

    search();
  });

  const search = async () => {
    try {
      // Convertir FormData en objet JS
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
      console.log(data);
    } catch (error) {
      console.error("Erreur : ", error);
    }
  };
});
