export default function initPagination() {
  document.addEventListener("click", e => {
    const link = e.target.closest("[data-page]");
    if (!link) return;

    const pagination = link.closest("[data-pagination]");
    if (!pagination) return;

    e.preventDefault();

    //on remonte au form fetch-form directement depuis la page
    const form = document.querySelector("form[data-module='fetch-form']");
    if (!form) return;

    const page = link.dataset.page;

    const url = new URL(form.dataset.fetchUrl, window.location.origin);

    // on récupère les filtres actuels du form
    const formData = new FormData(form);

    formData.forEach((value, key) => {
      url.searchParams.append(key, value);
    });

    url.searchParams.set("page", page);

    fetch(url.toString(), {
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    })
      .then(r => r.json())
      .then(data => {
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
