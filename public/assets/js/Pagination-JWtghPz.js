/**
 * Pagination globale générique
 * - 1 seul listener global
 * - fonctionne sur tous les listings
 * - aucune dépendance au formulaire
 * - aucun ID hardcodé
 */
let paginationInitialized = false;

export default function initPagination() {
  if (paginationInitialized) return;

  paginationInitialized = true;

  document.addEventListener("click", e => {
    const link = e.target.closest("[data-page]");
    if (!link) return;

    e.preventDefault();

    const listing = link.closest("[data-listing]");
    if (!listing) return;

    const page = link.dataset.page;

    // source de vérité = URL actuelle
    const url = new URL(window.location.href);
    const params = new URLSearchParams(url.search);

    params.set("page", page);

    const fetchUrl = new URL(listing.dataset.fetchUrl, window.location.origin);

    fetchUrl.search = params.toString();

    fetch(fetchUrl.toString(), {
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    })
      .then(r => r.json())
      .then(data => {
        // liste des résultats
        const target = document.querySelector(listing.dataset.target);

        if (target && data.list) {
          target.innerHTML = data.list;
        }

        // pagination du haut
        if (listing.dataset.paginationTop && data.paginationTop) {
          const top = document.querySelector(listing.dataset.paginationTop);

          if (top) {
            top.innerHTML = data.paginationTop;
          }
        }

        // pagination du bas
        if (listing.dataset.paginationBottom && data.paginationBottom) {
          const bottom = document.querySelector(listing.dataset.paginationBottom);

          if (bottom) {
            bottom.innerHTML = data.paginationBottom;
          }
        }

        // résumé des filtres
        if (listing.dataset.summary && data.filtersSummary) {
          const summary = document.querySelector(listing.dataset.summary);

          if (summary) {
            summary.innerHTML = data.filtersSummary;
          }
        }

        window.dispatchEvent(new Event("ui:updated"));
      });
  });
}
