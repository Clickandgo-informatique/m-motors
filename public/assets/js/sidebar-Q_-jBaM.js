/**
 * Sidebar toggle
 * Version sécurisée : aucun crash possible si élément absent
 * Compatible AJAX / pages partielles
 */

export default function initSidebar() {
  console.log("Sidebar.js initialisé");
  const toggle = document.getElementById("sidebar-toggle");
  const body = document.body;

  if (!toggle) {
    console.warn("Sidebar: toggle introuvable");
    return;
  }

  // Évite double binding si réinitialisation
  if (toggle.dataset.initialized === "1") return;
  toggle.dataset.initialized = "1";

  toggle.addEventListener("click", () => {
    body.classList.toggle("sidebar-collapsed");
  });
}
