/*
 * sidebar + filters drawer
 */

export default function initSidebar() {
  console.log("sidebar.js initialise");

  const body = document.body;

  const sidebar = document.getElementById("sidebar");
  const toggle = document.getElementById("sidebar-toggle");

  const groups = document.querySelectorAll(".sidebar-group");

  const filtersFab = document.getElementById("filters-fab");
  const filtersDrawer = document.getElementById("filters-drawer");
  const filtersClose = document.getElementById("filters-drawer-close");
  const filtersBackdrop = document.getElementById("filters-backdrop");

  if (toggle && toggle.dataset.initialized !== "1") {
    toggle.dataset.initialized = "1";

    toggle.addEventListener("click", () => {
      const isCollapsed = body.classList.toggle("sidebar-collapsed");

      if (isCollapsed) {
        groups.forEach(group => group.removeAttribute("open"));
      }
    });
  }

  if (sidebar) {
    const links = sidebar.querySelectorAll("a");

    links.forEach(link => {
      if (link.dataset.initialized === "1") return;

      link.dataset.initialized = "1";

      link.addEventListener("click", () => {
        if (body.classList.contains("sidebar-collapsed")) {
          body.classList.remove("sidebar-collapsed");
        }
      });
    });
  }

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

      body.classList.add("filters-open");
      body.style.overflow = "hidden";
    };

    const closeDrawer = () => {
      filtersDrawer.classList.remove("is-open");
      filtersBackdrop.classList.remove("is-visible");

      body.classList.remove("filters-open");
      body.style.overflow = "";
    };

    filtersFab.addEventListener("click", openDrawer);
    filtersClose.addEventListener("click", closeDrawer);
    filtersBackdrop.addEventListener("click", closeDrawer);
  }

  if (body.classList.contains("sidebar-collapsed")) {
    groups.forEach(group => group.removeAttribute("open"));
  }
}
