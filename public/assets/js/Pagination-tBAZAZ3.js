import EventBus from "./EventBus.js";

export default function initPagination(root = document) {
  const bind = container => {
    container.querySelectorAll("[data-pagination]").forEach(pagination => {
      pagination.querySelectorAll("[data-page]").forEach(link => {
        if (link.dataset.bound === "1") return;

        link.dataset.bound = "1";

        link.addEventListener("click", e => {
          e.preventDefault();

          EventBus.emit("pagination:changed", link.dataset.page);
        });
      });
    });
  };

  bind(root);

  window.addEventListener("ui:updated", () => bind(document));
}