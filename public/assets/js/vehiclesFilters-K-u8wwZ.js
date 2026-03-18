document.addEventListener("DOMContentLoaded", () => {
  const filterForm = document.getElementById("filters-form");
  const formData = new FormData(filterForm);

  filterForm.addEventListener("change", event => {
    // Vérifie que c'est bien une checkbox
    if (event.target.matches('input[type="checkbox"]')) {
      // Sélectionne directement toutes les checkboxes cochées dans le formulaire
      const selectedCheckboxes = filterForm.querySelectorAll(
        'input[type="checkbox"]:checked'
      );

      console.log("Checkboxes cochées :", selectedCheckboxes);
      formData.forEach(key => value => {
        console.log(key, value);
      });
    }
  });
});
