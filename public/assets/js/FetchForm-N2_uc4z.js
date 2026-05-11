export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.isLoading = false;

    this.init();
  }

  init() {
    this.form.addEventListener("submit", async e => {
      e.preventDefault();
      e.stopPropagation();

      await this.send();
    });
  }

  async send() {
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl;
    const mode = this.form.dataset.fetchMode || "html";

    const target = this.form.dataset.target
      ? document.querySelector(this.form.dataset.target)
      : null;

    const filtersTarget = this.form.dataset.filtersTarget
      ? document.querySelector(this.form.dataset.filtersTarget)
      : null;

    const paginationTopSelector =
      this.form.dataset.paginationTop || "#vehicles-pagination .pagination-top";

    const paginationBottomSelector =
      this.form.dataset.paginationBottom || "#vehicles-pagination-bottom";

    if (!url || !target) {
      console.error("[FetchForm] missing url or target");
      return;
    }

    this.isLoading = true;

    try {
      const formData = new FormData(this.form);
      const params = new URLSearchParams(formData);

      const fullUrl = url + "?" + params.toString();

      const res = await fetch(fullUrl, {
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      // =========================
      // MODE HTML (dashboard)
      // =========================
      if (mode === "html") {
        const html = await res.text();
        target.innerHTML = html;

        window.dispatchEvent(new Event("ui:updated"));
        return;
      }

      // =========================
      // MODE JSON (listing public)
      // =========================
      const data = await res.json();

      console.log("[FetchForm] response:", data);

      // LIST
      if (data.list) {
        target.innerHTML = data.list;
      }

      // FILTERS SIDEBAR
      if (data.filters && filtersTarget) {
        filtersTarget.innerHTML = data.filters;
      }

      // PAGINATION TOP
      const paginationTop = document.querySelector(paginationTopSelector);
      if (paginationTop && data.pagination_top) {
        paginationTop.innerHTML = data.pagination_top;
      }

      // PAGINATION BOTTOM
      const paginationBottom = document.querySelector(paginationBottomSelector);
      if (paginationBottom && data.pagination_bottom) {
        paginationBottom.innerHTML = data.pagination_bottom;
      }

      window.dispatchEvent(new Event("ui:updated"));
    } catch (err) {
      console.error("[FetchForm] error", err);
    } finally {
      this.isLoading = false;
    }
  }
}
