export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    this.form = form;
    this.isLoading = false;
    this.abortController = new AbortController();

    this.isReady = false;
    this._timeout = null;

    this.lastTrigger = "submit";

    this.init();
  }

  init() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();

      this.lastTrigger = "submit";

      this.send();
    });

    document.addEventListener("change", e => {
      if (!this.isReady) return;
      if (!this.form.contains(e.target)) return;

      this.lastTrigger = "filter";

      this.scheduleSend();
    });

    document.addEventListener("input", e => {
      if (!this.isReady) return;
      if (!this.form.contains(e.target)) return;

      this.lastTrigger = "filter";

      this.scheduleSend();
    });

    setTimeout(() => {
      this.isReady = true;
    }, 200);
  }

  scheduleSend() {
    clearTimeout(this._timeout);

    this._timeout = setTimeout(() => {
      this.send();
    }, 120);
  }

  async send() {
    if (this.isLoading) {
      return;
    }

    if (this.lastTrigger === "filter") {
      const pageInput = this.form.querySelector('[name="page"]');

      if (pageInput) {
        pageInput.value = 1;
      }
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

      if (data.results) {
        target.innerHTML = data.results;
      } else if (data.list) {
        target.innerHTML = data.list;
      }

      const paginationTopSelector = this.form.dataset.paginationTop;

      if (paginationTopSelector && data.paginationTop) {
        const top = document.querySelector(paginationTopSelector);
        if (top) top.innerHTML = data.paginationTop;
      }

      const paginationBottomSelector = this.form.dataset.paginationBottom;

      if (paginationBottomSelector && data.paginationBottom) {
        const bottom = document.querySelector(paginationBottomSelector);
        if (bottom) bottom.innerHTML = data.paginationBottom;
      }

      if (data.pagination) {
        const top = paginationTopSelector ? document.querySelector(paginationTopSelector) : null;

        const bottom = paginationBottomSelector
          ? document.querySelector(paginationBottomSelector)
          : null;

        if (top) top.innerHTML = data.pagination;
        if (bottom) bottom.innerHTML = data.pagination;
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

      if (window.__filterBadges?.updateBadges) {
        window.__filterBadges.updateBadges();
      }
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
