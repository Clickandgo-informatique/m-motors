document.addEventListener("DOMContentLoaded", () => {
  const filterForm = document.getElementById("filters-form");
  // Délégation : on écoute les changements sur le formulaire et on filtre les events
  filterForm.addEventListener("change", event => {
    // Vérifie que c'est bien une checkbox qui a changé
    if (event.target.matches(".checkbox")) {
      // Récupère toutes les checkboxes cochées
      const selectedCheckboxes = Array.from(
        filterForm.querySelectorAll(".checkbox")
      ).filter(cb => cb.checked);

      console.log("Checkboxes cochées :", selectedCheckboxes);
    }
  });

  const formData = new FormData(filterForm);
  formData.forEach(key => value => {
    console.log(key, value);
  });
});
