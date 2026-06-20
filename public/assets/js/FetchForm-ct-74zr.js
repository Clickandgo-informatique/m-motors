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

      // reset page uniquement si ce n’est PAS la pagination
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
    if (this.isLoading) return;

    this.abortController?.abort();
    this.abortController = new AbortController();

    this.isLoading = true;

    const url = this.form.dataset.fetchUrl;
    const target = document.querySelector(this.form.dataset.target);

    if (!target) {
      this.isLoading = false;
      return;
    }

    try {
      const params = new URLSearchParams(new FormData(this.form));

      const response = await fetch(`${url}?${params}`, {
        method: "GET",
        signal: this.abortController.signal
      });

      const data = await response.json();

      if (data.results) {
        target.innerHTML = data.results;
      }

      if (data.paginationTop) {
        const top = document.querySelector(this.form.dataset.paginationTop);
        if (top) top.innerHTML = data.paginationTop;
      }

      if (data.paginationBottom) {
        const bottom = document.querySelector(this.form.dataset.paginationBottom);
        if (bottom) bottom.innerHTML = data.paginationBottom;
      }

      if (data.filtersSummary) {
        const summary = document.querySelector(
          this.form.dataset.filtersTarget || "#filters-summary"
        );

        if (summary) {
          summary.innerHTML = data.filtersSummary;
        }
      }

      // IMPORTANT : toujours après injection DOM
      window.dispatchEvent(new Event("ui:updated"));

      window.__filterBadges?.updateBadges?.();
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("[FetchForm]", e);
      }
    } finally {
      this.isLoading = false;
    }
  }
}
