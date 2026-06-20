export default function initPagination(root = document) {
  /**
   * Bind pagination links
   */
  const bind = container => {
    container.querySelectorAll("[data-pagination]").forEach(pagination => {
      pagination.querySelectorAll("[data-page]").forEach(link => {
        if (link.dataset.bound === "1") {
          return;
        }

        link.dataset.bound = "1";

        link.addEventListener("click", e => {
          e.preventDefault();

          const form = document.querySelector("[data-module='fetch-form']");

          if (!form) {
            return;
          }

          /**
           * SOURCE DE VERITE UNIQUE :
           * dataset.page (évite conflits input + FormData + change events)
           */
          form.dataset.page = link.dataset.page;

          /**
           * Supprime ancien input page si présent
           * (évite doublons qui écrasent la valeur dans FormData)
           */
          const oldInput = form.querySelector("input[name='page']");
          if (oldInput) {
            oldInput.remove();
          }

          /**
           * Déclenche le fetchForm proprement
           */
          form.dispatchEvent(
            new Event("change", {
              bubbles: true
            })
          );
        });
      });
    });
  };

  /**
   * premier bind
   */
  bind(root);

  /**
   * rebind après AJAX update
   */
  window.addEventListener("ui:updated", () => {
    bind(document);
  });
}
