/**
 * Gestion de la pagination AJAX
 * - Injecte la page dans le formulaire fetch-form
 * - Déclenche un change global
 * - Marque explicitement l'événement comme pagination
 */
export default function initPagination(root = document) {
  const bind = container => {
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

          // Champ page (création si nécessaire)
          let input = form.querySelector("input[name='page']");

          if (!input) {
            input = document.createElement("input");
            input.type = "hidden";
            input.name = "page";
            form.appendChild(input);
          }

          // Mise à jour page
          input.value = page;

          /**
           * IMPORTANT :
           * Permet de distinguer pagination vs filtres
           */
          form._isPaginationEvent = true;

          // Déclenche le système fetch-form
          form.dispatchEvent(new Event("change", { bubbles: true }));
        });
      });
    });
  };

  // Bind initial
  bind(root);

  /**
   * Rebind après injection AJAX
   */
  window.addEventListener("ui:updated", () => {
    bind(document);
  });
}
