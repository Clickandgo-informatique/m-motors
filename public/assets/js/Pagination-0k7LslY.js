export default function initPagination() {
  document.addEventListener("click", e => {
    const link = e.target.closest("[data-page]");
    if (!link) return;

    e.preventDefault();

    const form = link.closest("form[data-module='fetch-form']");
    if (!form) return;

    const page = link.dataset.page;

    let input = form.querySelector("input[name='page']");

    if (!input) {
      input = document.createElement("input");
      input.type = "hidden";
      input.name = "page";
      form.appendChild(input);
    }

    input.value = page;

    form._fromPagination = true;

    form.dispatchEvent(
      new CustomEvent("fetch-form:submit", {
        bubbles: true
      })
    );

    form._fromPagination = false;
  });
}
