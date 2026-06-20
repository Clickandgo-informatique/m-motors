export default function initPagination(root = document) {
  root.querySelectorAll("[data-pagination]").forEach(pagination => {
    pagination.querySelectorAll("[data-page]").forEach(link => {
      if (link.dataset.bound === "1") {
        return;
      }

      link.dataset.bound = "1";

      link.addEventListener("click", e => {
        e.preventDefault();

        const page = link.dataset.page;

        const form = document.querySelector("[data-module='fetch-form']");

        if (!form) {
          console.warn("[Pagination] Aucun formulaire fetch-form trouvé.");
          return;
        }

        let input = form.querySelector("input[name='page']");

        if (!input) {
          input = document.createElement("input");
          input.type = "hidden";
          input.name = "page";
          form.appendChild(input);
        }

        // IMPORTANT :
        // seule la pagination modifie la page
        input.value = page;

        form.dispatchEvent(new Event("change", { bubbles: true }));
      });
    });
  });
}
