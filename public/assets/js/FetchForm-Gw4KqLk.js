export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.isLoading = false;
    this.abortController = new AbortController();

    this.isReady = false;

    this.init();
  }

  init() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    let timeout = null;

    this.form.addEventListener("change", () => {
      if (!this.isReady) return;

      clearTimeout(timeout);
      timeout = setTimeout(() => this.send(), 120);
    });

    setTimeout(() => {
      this.isReady = true;
    }, 300);
  }

  async send() {
    if (this.isLoading) return;

    this.abortController?.abort();
    this.abortController = new AbortController();

    this.isLoading = true;
    this.form.dataset.loading = "1";

    const url = this.form.dataset.fetchUrl;

    const target = document.querySelector(this.form.dataset.target);
    if (!target) return;

    try {
      const formData = new FormData(this.form);

      this.form.querySelectorAll("input, select, textarea").forEach(el => {
        if (!el.name) return;
        formData.set(el.name, el.value);
      });

      const params = new URLSearchParams();

      formData.forEach((value, key) => {
        params.append(key, value);
      });

      const res = await fetch(`${url}?${params.toString()}`, {
        method: "GET",
        signal: this.abortController.signal
      });

      const data = await res.json();

      if (data.list) {
        target.innerHTML = data.list;
      }

      const top = document.querySelector("#vehicles-pagination-top");
      const bottom = document.querySelector("#vehicles-pagination-bottom");

      if (data.pagination) {
        if (top) top.innerHTML = data.pagination;
        if (bottom) bottom.innerHTML = data.pagination;
      }

      if (data.filtersSummary) {
        const summary = document.querySelector("#filters-summary");
        if (summary) summary.innerHTML = data.filtersSummary;
      }

      window.dispatchEvent(new Event("ui:updated"));
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("[FetchForm]", e);
      }
    } finally {
      this.isLoading = false;
      delete this.form.dataset.loading;
    }
  }
}
