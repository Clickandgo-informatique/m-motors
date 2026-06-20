/**
 * Pagination globale générique
 * - 1 seul listener global
 * - fonctionne sur tous les blocs [data-pagination]
 * - aucun ID hardcodé
 */
let paginationInitialized = false;

export default function initPagination() {
  if (paginationInitialized) return;

  paginationInitialized = true;

  document.addEventListener("click", (e) => {
    const link = e.target.closest("[data-page]");
    if (!link) return;

    const pagination = link.closest("[data-pagination]");
    if (!pagination) return;

    e.preventDefault();

    const form = document.querySelector("form[data-module='fetch-form']");
    if (!form) return;

    const page = link.dataset.page;

    // source de vérité = URL actuelle
    const url = new URL(window.location.href);
    const params = new URLSearchParams(url.search);

    params.set("page", page);

    const fetchUrl = new URL(form.dataset.fetchUrl, window.location.origin);
    fetchUrl.search = params.toString();

    fetch(fetchUrl.toString(), {
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    })
      .then((r) => r.json())
      .then((data) => {
        const target = document.querySelector(form.dataset.target);

        // mise à jour liste
        if (target && data.list) {
          target.innerHTML = data.list;
        }

        // mise à jour pagination (tous les blocs présents dans la page)
        document
          .querySelectorAll("[data-pagination-wrapper]")
          .forEach((wrapper) => {
            if (wrapper.dataset.position === "top" && data.paginationTop) {
              wrapper.innerHTML = data.paginationTop;
            }

            if (wrapper.dataset.position === "bottom" && data.paginationBottom) {
              wrapper.innerHTML = data.paginationBottom;
            }
          });
        window.dispatchEvent(new Event("ui:updated"));
      });
  });
}