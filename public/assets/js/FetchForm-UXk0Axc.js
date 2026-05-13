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

  warnMissingDataset(key, value) {
    if (!value) {
      console.warn(`[FetchForm] Missing dataset: "${key}"`, this.form);
      return false;
    }
    return true;
  }

  resolveTarget(selector, name) {
    if (!selector) {
      console.warn(`[FetchForm] Missing dataset for ${name}`);
      return null;
    }

    const el = document.querySelector(selector);

    if (!el) {
      console.warn(`[FetchForm] Target not found for ${name}: ${selector}`);
    }

    return el;
  }

  async send() {
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl;
    const mode = this.form.dataset.fetchMode || "json";

    if (!this.warnMissingDataset("fetchUrl", url)) return;

    const target = this.resolveTarget(this.form.dataset.target, "target");
    const filtersTarget = this.resolveTarget(this.form.dataset.filtersTarget, "filtersTarget");
    const paginationTop = this.resolveTarget(this.form.dataset.paginationTop, "paginationTop");
    const paginationBottom = this.resolveTarget(
      this.form.dataset.paginationBottom,
      "paginationBottom"
    );

    if (!target) {
      console.error("[FetchForm] Missing target container");
      return;
    }

    this.isLoading = true;

    try {
      const formData = new FormData(this.form);
      const params = new URLSearchParams(formData);

      const res = await fetch(url, {
        method: "POST",
        body: params
      });

      if (mode === "html") {
        const html = await res.text();
        target.innerHTML = html;
        window.dispatchEvent(new Event("ui:updated"));
        return;
      }

      const data = await res.json();

      if (data.list) {
        target.innerHTML = data.list;
      }

      if (data.pagination_top && paginationTop) {
        paginationTop.innerHTML = data.pagination_top;
      }

      if (data.pagination_bottom && paginationBottom) {
        paginationBottom.innerHTML = data.pagination_bottom;
      }

      if (data.filters && filtersTarget) {
        filtersTarget.innerHTML = data.filters;
      }

      if (data.filtersSummary) {
        const summary = document.querySelector("#filters-summary");
        if (summary) summary.innerHTML = data.filtersSummary;
      }

      window.dispatchEvent(new Event("ui:updated"));
    } catch (err) {
      console.error("[FetchForm] erreur AJAX", err);
    } finally {
      this.isLoading = false;
    }
  }
}
