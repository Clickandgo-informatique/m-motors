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
      if (!this.isReady) {
        return;
      }

      // IMPORTANT :
      // tout changement de filtre reset la pagination
      const pageInput = this.form.querySelector("input[name='page']");
      if (pageInput) {
        pageInput.value = 1;
      }

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
    if (this.isLoading) {
      return;
    }

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

      formData.forEach((value, key) => {
        params.append(key, value);
      });

      const response = await fetch(`${url}?${params.toString()}`, {
        method: "GET",
        signal: this.abortController.signal
      });

      const data = await response.json();

      // results
      if (data.results) {
        target.innerHTML = data.results;
      } else if (data.list) {
        target.innerHTML = data.list;
      }

      // pagination top
      const topSelector = this.form.dataset.paginationTop;
      if (topSelector && data.paginationTop) {
        const top = document.querySelector(topSelector);
        if (top) top.innerHTML = data.paginationTop;
      }

      // pagination bottom
      const bottomSelector = this.form.dataset.paginationBottom;
      if (bottomSelector && data.paginationBottom) {
        const bottom = document.querySelector(bottomSelector);
        if (bottom) bottom.innerHTML = data.paginationBottom;
      }

      // compat legacy
      if (data.pagination) {
        const top = topSelector ? document.querySelector(topSelector) : null;
        const bottom = bottomSelector ? document.querySelector(bottomSelector) : null;

        if (top) top.innerHTML = data.pagination;
        if (bottom) bottom.innerHTML = data.pagination;
      }

      // filters summary
      if (data.filtersSummary) {
        const summary = document.querySelector(
          this.form.dataset.filtersTarget || "#filters-summary"
        );

        if (summary) {
          summary.innerHTML = data.filtersSummary;
        }
      }

      // IMPORTANT :
      // les badges doivent être mis à jour après DOM injection
      window.requestAnimationFrame(() => {
        window.__filterBadges?.updateBadges?.();
        window.dispatchEvent(new Event("ui:updated"));
      });
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
