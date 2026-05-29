export default function initSidebar() {
  console.log("sidebar.js initialisé");

  const body = document.body;

  const sidebar = document.getElementById("sidebar");
  const toggle = document.getElementById("sidebar-toggle");

  const groups = document.querySelectorAll(".sidebar-group");

  const filtersFab = document.getElementById("filters-fab");
  const filtersDrawer = document.getElementById("filters-drawer");
  const filtersClose = document.getElementById("filters-drawer-close");
  const filtersBackdrop = document.getElementById("filters-backdrop");

  /**
   * etat initial sidebar
   * force la cohérence (évite "ouverte au démarrage")
   */
  const initCollapsed = () => {
    const isCollapsed = body.classList.contains("sidebar-collapsed");

    if (isCollapsed) {
      groups.forEach(g => g.removeAttribute("open"));
    }
  };

  initCollapsed();

  /**
   * toggle sidebar
   */
  if (toggle && toggle.dataset.initialized !== "1") {
    toggle.dataset.initialized = "1";

    toggle.addEventListener("click", () => {
      const isCollapsed = body.classList.toggle("sidebar-collapsed");

      if (isCollapsed) {
        groups.forEach(group => group.removeAttribute("open"));
      }
    });
  }

  /**
   * clic sur navigation
   * réouvre sidebar si collapsed
   */
  if (sidebar) {
    const clickableElements = sidebar.querySelectorAll("a, .sidebar-group summary");

    clickableElements.forEach(el => {
      if (el.dataset.initialized === "1") return;

      el.dataset.initialized = "1";

      el.addEventListener("click", () => {
        body.classList.remove("sidebar-collapsed");
      });
    });
  }

  /**
   * drawer filtres
   */
  if (
    filtersFab &&
    filtersDrawer &&
    filtersClose &&
    filtersBackdrop &&
    filtersFab.dataset.initialized !== "1"
  ) {
    filtersFab.dataset.initialized = "1";

    const openDrawer = () => {
      filtersDrawer.classList.add("is-open");
      filtersBackdrop.classList.add("is-visible");
      body.style.overflow = "hidden";
    };

    const closeDrawer = () => {
      filtersDrawer.classList.remove("is-open");
      filtersBackdrop.classList.remove("is-visible");
      body.style.overflow = "";
    };

    filtersFab.addEventListener("click", openDrawer);
    filtersClose.addEventListener("click", closeDrawer);
    filtersBackdrop.addEventListener("click", closeDrawer);
  }
}
