// Initialise les liens de pagination présents dans la page ou dans une zone
// injectée dynamiquement par AJAX.
export default function initPagination(root = document) {
  root.querySelectorAll("[data-pagination]").forEach(pagination => {
    pagination.querySelectorAll("[data-page]").forEach(link => {
      // Évite de rattacher plusieurs fois le même listener
      if (link.dataset.bound === "1") {
        return;
      }

      link.dataset.bound = "1";

      link.addEventListener("click", e => {
        e.preventDefault();

        const page = link.dataset.page;

        // Recherche le premier formulaire utilisant le module fetch-form
        const form = document.querySelector("[data-module='fetch-form']");

        if (!form) {
          console.warn("[Pagination] Aucun formulaire fetch-form trouvé.");
          return;
        }

        // Récupère ou crée le champ page
        let input = form.querySelector("input[name='page']");

        if (!input) {
          input = document.createElement("input");
          input.type = "hidden";
          input.name = "page";

          form.appendChild(input);
        }

        // Met à jour la page demandée
        input.value = page;

        // Déclenche une nouvelle recherche AJAX
        form.dispatchEvent(
          new Event("change", {
            bubbles: true
          })
        );
      });
    });
  });
}
