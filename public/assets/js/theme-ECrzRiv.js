// ===============================
// SWITCHER DE THÈME
// ===============================

const toggleBtn = document.getElementById("theme-toggle");

// Charger le thème sauvegardé
if (localStorage.getItem("theme") === "dark") {
  document.documentElement.classList.add("dark");
}

// Activer/désactiver le thème
document.addEventListener("click", e => {
  const btn = e.target.closest("#theme-toggle");
  if (!btn) return;

  document.documentElement.classList.toggle("dark");

  const isDark = document.documentElement.classList.contains("dark");
  localStorage.setItem("theme", isDark ? "dark" : "light");
});

// ===============================
// MENU HAMBURGER
// ===============================

const navToggle = document.querySelector(".nav-toggle");
const navList = document.querySelector(".navbar-links");
if (navToggle && navList) {
  navToggle.addEventListener("click", () => {
    navList.classList.toggle("open");
  });
}
