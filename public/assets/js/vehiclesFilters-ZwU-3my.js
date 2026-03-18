document.addEventListener("DOMContentLoaded", () => {
  const filterForm = document.getElementById("filters-form");
  console.log(filterForm);

  const formData = new FormData(filterForm);
  formData.forEach(key => value => {
    console.log(key, value);
  });
});
