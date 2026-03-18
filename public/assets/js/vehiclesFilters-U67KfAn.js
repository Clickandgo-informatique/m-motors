document.addEventListener("DOMContentLoaded", () => {
  const filterForm = document.getElementById("filters-form");
  const selectedCheckboxes = filterForm.querySelectorAll(".checkbox[checked]");
  console.log(filterForm);
  console.log(selectedCheckboxes);

  const formData = new FormData(filterForm);
  formData.forEach(key => value => {
    console.log(key, value);
  });
});
