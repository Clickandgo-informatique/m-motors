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

    // EVENT PROPRE pour pagination + triggers externes
    this.form.addEventListener("fetch-form:submit", e => {
      e.preventDefault();
      this.send();
    });

    // CHANGE uniquement pour filtres (debounced)
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

      // RESET PAGE UNIQUEMENT SI FILTRES (pas pagination)
      if (!this.form._fromPagination) {
        let pageInput = this.form.querySelector("input[name='page']");

        if (!pageInput) {
          pageInput = document.createElement("input");
          pageInput.type = "hidden";
          pageInput.name = "page";
          this.form.appendChild(pageInput);
        }

        pageInput.value = 1;
      }

      const params = new URLSearchParams();

      formData.forEach((value, key) => {
        if (value !== null && value !== "") {
          params.append(key, value);
        }
      });

      // URL propre navigateur
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

      // LISTING
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

      // FILTRES SUMMARY
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
