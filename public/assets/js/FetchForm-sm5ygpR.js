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

    const paginationTop = this.form.dataset.paginationTop
      ? document.querySelector(this.form.dataset.paginationTop)
      : null;

    const paginationBottom = this.form.dataset.paginationBottom
      ? document.querySelector(this.form.dataset.paginationBottom)
      : null;

    if (!url || !target) {
      console.error("[FetchForm] missing url or target");
      return;
    }

    this.isLoading = true;

    try {
      const formData = new FormData(this.form);
      const params = new URLSearchParams(formData);

      const res = await fetch(url + "?" + params.toString(), {
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      // =========================
      // HTML MODE (dashboard)
      // =========================
      if (mode === "html") {
        const html = await res.text();
        target.innerHTML = html;

        window.dispatchEvent(new Event("ui:updated"));
        return;
      }

      // =========================
      // JSON MODE (listing)
      // =========================
      const data = await res.json();

      if (data.list && target) {
        target.innerHTML = data.list;
      }

      if (data.filters && filtersTarget) {
        filtersTarget.innerHTML = data.filters;
      }

      if (data.pagination_top && paginationTop) {
        paginationTop.innerHTML = data.pagination_top;
      }

      if (data.pagination_bottom && paginationBottom) {
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
