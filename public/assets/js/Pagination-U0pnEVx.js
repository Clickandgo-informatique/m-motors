export default function initPagination(root = document) {
  root.querySelectorAll("[data-pagination]").forEach(pagination => {
    pagination.querySelectorAll("[data-page]").forEach(link => {
      if (link.dataset.bound === "1") return;

      link.dataset.bound = "1";

      link.addEventListener("click", e => {
        e.preventDefault();

        const page = link.dataset.page;

        const form = pagination.closest("form[data-module='fetch-form']");

        if (!form) return;

        let input = form.querySelector("input[name='page']");

        if (!input) {
          input = document.createElement("input");
          input.type = "hidden";
          input.name = "page";
          form.appendChild(input);
        }

        input.value = page;

        //flag pagination
        form.dataset.fromPagination = "1";

        form.dispatchEvent(new Event("change", { bubbles: true }));
      });
    });
  });
}
