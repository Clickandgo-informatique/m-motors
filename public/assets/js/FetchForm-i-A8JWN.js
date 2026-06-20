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
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    let timeout = null;

    this.form.addEventListener("change", () => {
      if (!this.isReady) return;

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

    if (!target) {
      this.isLoading = false;
      delete this.form.dataset.loading;
      return;
    }

    try {
      const formData = new FormData(this.form);

      const params = new URLSearchParams();

      // Si ce n'est PAS une action pagination → reset page
      if (!this.form._fromPagination) {
        const pageInput = this.form.querySelector("input[name='page']");
        if (pageInput) {
          pageInput.value = 1;
        }
      }

      formData.forEach((value, key) => {
        params.append(key, value);
      });

      const browserUrl = `${window.location.pathname}?${params.toString()}`;
      window.history.replaceState({}, "", browserUrl);

      const response = await fetch(`${url}?${params.toString()}`, {
        method: "GET",
        signal: this.abortController.signal
      });

      const data = await response.json();

      if (data.results) {
        target.innerHTML = data.results;
      } else if (data.list) {
        target.innerHTML = data.list;
      }

      const paginationTopSelector = this.form.dataset.paginationTop;
      const paginationBottomSelector = this.form.dataset.paginationBottom;

      if (paginationTopSelector && data.paginationTop) {
        const top = document.querySelector(paginationTopSelector);
        if (top) {
          top.innerHTML = data.paginationTop;
          initPagination(top);
        }
      }

      if (paginationBottomSelector && data.paginationBottom) {
        const bottom = document.querySelector(paginationBottomSelector);
        if (bottom) {
          bottom.innerHTML = data.paginationBottom;
          initPagination(bottom);
        }
      }

      if (data.filtersSummary) {
        const summary = document.querySelector(
          this.form.dataset.filtersTarget || "#filters-summary"
        );

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
