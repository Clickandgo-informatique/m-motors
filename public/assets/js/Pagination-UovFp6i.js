// Initialise les liens de pagination présents dans la page ou injectés via AJAX
export default function initPagination(root = document) {
  root.querySelectorAll("[data-pagination]").forEach(pagination => {
    pagination.querySelectorAll("[data-page]").forEach(link => {
      // Évite les doubles bindings
      if (link.dataset.bound === "1") {
        return;
      }

      link.dataset.bound = "1";

      link.addEventListener("click", e => {
        e.preventDefault();

        const page = link.dataset.page;

        // Récupère le formulaire fetch-form principal
        const form = document.querySelector("[data-module='fetch-form']");

        if (!form) {
          console.warn("[Pagination] Aucun formulaire fetch-form trouvé.");
          return;
        }

        // Champ page (créé si absent)
        let input = form.querySelector("input[name='page']");

        if (!input) {
          input = document.createElement("input");
          input.type = "hidden";
          input.name = "page";
          form.appendChild(input);
        }

        // Indique que la requête vient de la pagination
        form.dataset.paginationAction = "1";

        // Met à jour la page demandée
        input.value = page;

        // Déclenche le fetch via le système existant
        form.dispatchEvent(
          new Event("change", {
            bubbles: true
          })
        );
      });
    });
  });
}
