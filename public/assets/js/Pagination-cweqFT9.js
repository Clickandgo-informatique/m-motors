/**
 * Module de pagination AJAX
 * - Gère les clics sur les pages
 * - Injecte la page dans le formulaire fetch-form
 * - Compatible DOM dynamique (AJAX re-render)
 */
export default function initPagination(root = document) {
  /**
   * Fonction de binding des événements sur les liens de pagination
   */
  const bind = container => {
    container.querySelectorAll("[data-pagination]").forEach(pagination => {
      pagination.querySelectorAll("[data-page]").forEach(link => {
        /**
         * Protection contre double binding
         */
        if (link.dataset.bound === "1") {
          return;
        }

        link.dataset.bound = "1";

        link.addEventListener("click", e => {
          e.preventDefault();

          const form = document.querySelector("[data-module='fetch-form']");

          if (!form) {
            console.warn("[Pagination] fetch-form introuvable");
            return;
          }

          /**
           * Création ou récupération du champ page
           */
          let input = form.querySelector("input[name='page']");

          if (!input) {
            input = document.createElement("input");
            input.type = "hidden";
            input.name = "page";
            form.appendChild(input);
          }

          /**
           * Mise à jour de la page demandée
           */
          input.value = link.dataset.page;

          /**
           * Déclenchement du système FetchForm
           */
          form.dispatchEvent(new Event("change", { bubbles: true }));
        });
      });
    });
  };

  /**
   * Binding initial
   */
  bind(root);

  /**
   * Rebinding après chaque update AJAX
   */
  window.addEventListener("ui:updated", () => {
    bind(document);
  });

  /**
   * Expose une fonction globale utile pour debug ou refresh manuel
   */
  window.__initPagination = () => {
    bind(document);
  };
}
