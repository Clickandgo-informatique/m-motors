export default function initPagination(root = document) {
  const bind = (container) => {
    container.querySelectorAll("[data-pagination]").forEach(pagination => {
      pagination.querySelectorAll("[data-page]").forEach(link => {

        link.addEventListener("click", e => {
          e.preventDefault();

          const page = link.dataset.page;

          const form = document.querySelector("[data-module='fetch-form']");

          if (!form) {
            console.warn("[Pagination] fetch-form introuvable");
            return;
          }

          let input = form.querySelector("input[name='page']");

          if (!input) {
            input = document.createElement("input");
            input.type = "hidden";
            input.name = "page";
            form.appendChild(input);
          }

          input.value = page;

          form.dispatchEvent(
            new Event("change", { bubbles: true })
          );
        });
      });
    });
  };

  // bind initial
  bind(root);

  // IMPORTANT :
  // rebind après chaque AJAX injection
  window.addEventListener("ui:updated", () => {
    bind(document);
  });
}