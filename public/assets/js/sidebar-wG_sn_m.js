/**
 * Sidebar toggle
 * Version sécurisée : aucun crash possible si élément absent
 * Compatible AJAX / pages partielles
 */

export default function initSidebar() {
  console.log("Sidebar.js initialisé");

  const toggle = document.getElementById("sidebar-toggle");
  const body = document.body;
  const sidebar = document.getElementById("sidebar");
  const groups = document.querySelectorAll(".sidebar-group");

  if (!toggle) {
    console.warn("Sidebar: toggle introuvable");
    return;
  }

  // Évite double binding si réinitialisation AJAX
  if (toggle.dataset.initialized === "1") return;
  toggle.dataset.initialized = "1";

  toggle.addEventListener("click", () => {
    const isCollapsed = body.classList.toggle("sidebar-collapsed");

    /**
     * Si sidebar collapsed :
     * - on ferme tous les accordions
     * - on garantit un état propre
     */
    if (isCollapsed) {
      groups.forEach(group => {
        group.removeAttribute("open");
      });
    }
  });

  /**
   * UX: clic sur un lien => réouvre la sidebar si collapsed
   * (utile quand utilisateur navigue en mode icônes)
   */
  if (sidebar) {
    const clickableElements = sidebar.querySelectorAll("a, .sidebar-group summary");

    clickableElements.forEach(el => {
      if (el.dataset.initialized === "1") return;
      el.dataset.initialized = "1";

      el.addEventListener("click", () => {
        if (body.classList.contains("sidebar-collapsed")) {
          body.classList.remove("sidebar-collapsed");
        }
      });
    });
  }
}
