document.addEventListener("DOMContentLoaded", function() {
  const toggle = document.getElementById("sidebar-toggle");
  const body = document.body;

  toggle.addEventListener("click", function() {
    body.classList.toggle("sidebar-collapsed");
  });
});
