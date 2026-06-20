import initPagination from "./Pagination.js";

export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    this.form = form;
    this.isLoading = false;
    this.abortController = new AbortController();
    this.isReady = false;

    this.init();
  }

  init() {
    // submit manuel (fallback)
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    let timeout = null;

    this.form.addEventListener("change", () => {
      if (!this.isReady) return;

      if (this.form._fromPagination) return;

      clearTimeout(timeout);

      timeout = setTimeout(() => {
        this.send();
      }, 120);
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

    if (!url || !target) {
      this.isLoading = false;
      delete this.form.dataset.loading;
      return;
    }

    try {
      const formData = new FormData(this.form);
      const params = new URLSearchParams();

      // reset page uniquement si action filtres
      if (!this.form._fromPagination) {
        const pageInput = this.form.querySelector("input[name='page']");

        if (pageInput) {
          pageInput.value = 1;
        }
      }

      formData.forEach((value, key) => {
        if (value !== null && value !== "") {
          params.append(key, value);
        }
      });

      const browserUrl = `${window.location.pathname}?${params.toString()}`;
      window.history.replaceState({}, "", browserUrl);

      const response = await fetch(`${url}?${params.toString()}`, {
        method: "GET",
        signal: this.abortController.signal,
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      const data = await response.json();

      // LIST
      if (data.results) {
        target.innerHTML = data.results;
      } else if (data.list) {
        target.innerHTML = data.list;
      }

      // PAGINATION TOP
      const topSelector = this.form.dataset.paginationTop;
      if (topSelector && data.paginationTop) {
        const top = document.querySelector(topSelector);
        if (top) {
          top.innerHTML = data.paginationTop;
          initPagination(top);
        }
      }

      // PAGINATION BOTTOM
      const bottomSelector = this.form.dataset.paginationBottom;
      if (bottomSelector && data.paginationBottom) {
        const bottom = document.querySelector(bottomSelector);
        if (bottom) {
          bottom.innerHTML = data.paginationBottom;
          initPagination(bottom);
        }
      }

      // FILTER SUMMARY
      const summarySelector = this.form.dataset.filtersTarget || "#filters-summary";

      if (data.filtersSummary) {
        const summary = document.querySelector(summarySelector);
        if (summary) {
          summary.innerHTML = data.filtersSummary;
        }
      }

      window.dispatchEvent(new Event("ui:updated"));
      window.__filterBadges?.updateBadges();
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
