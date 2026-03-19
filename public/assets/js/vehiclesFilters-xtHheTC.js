document.addEventListener("DOMContentLoaded", () => {
  const filterForm = document.getElementById("filters-form");
  const formData = new FormData();

  filterForm.addEventListener("change", event => {
    const target = event.target;

    if (target.matches('input[type="checkbox"]')) {
      // Supprime l'ancienne valeur pour ce type (marque/fuel/etc)
      formData.delete(target.name);

      // Récupère toutes les checkboxes cochées pour ce nom
      const checked = filterForm.querySelectorAll(
        `input[name="${target.name}"]:checked`
      );

      checked.forEach(cb => {
        formData.append(cb.name, cb.value);
      });

      // Affiche ce que tu vas envoyer
      console.log("--- FormData ---");
      formData.forEach((value, key) => console.log(key, value));
    }
    search();
  });
  const search = async () => {
    console.log("méthode search initialisée");
    const response = await fetch("/vehicles/search2/", {
      headers: {
        method: "POST",
        // content:""
        body: formData
      }
    });
    const data = await response.json();
    console.log(data);
  };
});
