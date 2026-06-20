export default function initPagination() {
  document.addEventListener("click", (e) => {
    const link = e.target.closest("[data-page]");
    if (!link) return;

    const pagination = link.closest("[data-pagination]");
    if (!pagination) return;

    e.preventDefault();

    const form = document.querySelector("form[data-module='fetch-form']");
    if (!form) return;

    const page = link.dataset.page;

    // URL actuelle
    const url = new URL(window.location.href);

    // on garde les filtres actuels (déjà dans URL)
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

        if (target && data.list) {
          target.innerHTML = data.list;
        }

        const top = document.querySelector("#vehicles-pagination-top");
        const bottom = document.querySelector("#vehicles-pagination-bottom");

        if (top && data.paginationTop) {
          top.innerHTML = data.paginationTop;
        }

        if (bottom && data.paginationBottom) {
          bottom.innerHTML = data.paginationBottom;
        }

        const summary = document.querySelector("#filters-summary");
        if (summary && data.filtersSummary) {
          summary.innerHTML = data.filtersSummary;
        }

        window.dispatchEvent(new Event("ui:updated"));
      });
  });
}