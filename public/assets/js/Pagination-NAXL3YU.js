import EventBus from "./EventBus.js";

export default function initPagination(root = document) {
  const bind = container => {
    container.querySelectorAll("[data-pagination]").forEach(pagination => {
      pagination.querySelectorAll("[data-page]").forEach(link => {
        if (link.dataset.bound === "1") return;

        link.dataset.bound = "1";

        link.addEventListener("click", e => {
          e.preventDefault();

          const form = document.querySelector("[data-module='fetch-form']");
          if (!form) return;

          let input = form.querySelector("input[name='page']");

          if (!input) {
            input = document.createElement("input");
            input.type = "hidden";
            input.name = "page";
            form.appendChild(input);
          }

          input.value = link.dataset.page;

          EventBus.emit("pagination:changed", link.dataset.page);

          form.dispatchEvent(new Event("change", { bubbles: true }));
        });
      });
    });
  };

  bind(root);

  EventBus.on("filter:changed", () => {
    const form = document.querySelector("[data-module='fetch-form']");
    if (!form) return;

    let input = form.querySelector("input[name='page']");
    if (input) input.value = 1;
  });

  window.addEventListener("ui:updated", () => bind(document));
}
