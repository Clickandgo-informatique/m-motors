export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    this.form = form;
    this.abortController = null;
    this._timeout = null;

    this.init();
  }

  init() {
    this.form.addEventListener("change", e => {
      if (!this.form.contains(e.target)) return;

      this.resetPage();
      this.scheduleSend();
    });

    this.form.addEventListener("input", e => {
      if (!this.form.contains(e.target)) return;

      this.resetPage();
      this.scheduleSend();
    });

    this.form.addEventListener("click", e => {
      const link = e.target.closest("[data-page]");
      if (!link) return;

      e.preventDefault();
      this.goToPage(link.dataset.page);
    });

    this.initResetButton();
  }

  initResetButton() {
    const resetBtn = this.form.querySelector("[data-reset-filters]");
    if (!resetBtn) return;

    resetBtn.addEventListener("click", e => {
      e.preventDefault();

      this.form.reset();
      this.resetPage();

      this.send();
    });
  }

  resetPage() {
    let pageInput = this.form.querySelector('[name="page"]');

    if (!pageInput) {
      pageInput = document.createElement("input");
      pageInput.type = "hidden";
      pageInput.name = "page";
      this.form.appendChild(pageInput);
    }

    pageInput.value = 1;
  }

  goToPage(page) {
    let pageInput = this.form.querySelector('[name="page"]');

    if (!pageInput) {
      pageInput = document.createElement("input");
      pageInput.type = "hidden";
      pageInput.name = "page";
      this.form.appendChild(pageInput);
    }

    pageInput.value = page;

    this.send();
  }

  scheduleSend() {
    clearTimeout(this._timeout);

    this._timeout = setTimeout(() => {
      this.send();
    }, 120);
  }

  async send() {
    if (this.abortController) {
      this.abortController.abort();
    }

    this.abortController = new AbortController();

    const url = this.form.dataset.fetchUrl;
    const target = document.querySelector(this.form.dataset.target);

    if (!url || !target) return;

    const formData = new FormData(this.form);
    const params = new URLSearchParams();

    formData.forEach((value, key) => {
      if (value !== null && value !== "") {
        params.append(key, value);
      }
    });

    try {
      const response = await fetch(`${url}?${params.toString()}`, {
        method: "GET",
        signal: this.abortController.signal
      });

      const data = await response.json();

      this.renderTarget(target, data);
      this.renderPagination(data);
      this.renderFiltersSummary(data);

      window.dispatchEvent(new Event("ui:updated"));

    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("[FetchForm]", e);
      }
    }
  }

  renderTarget(target, data) {
    if (data.results) {
      target.innerHTML = data.results;
      return;
    }

    if (data.list) {
      target.innerHTML = data.list;
    }
  }

  renderPagination(data) {
    const top = this.form.dataset.paginationTop
      ? document.querySelector(this.form.dataset.paginationTop)
      : null;

    const bottom = this.form.dataset.paginationBottom
      ? document.querySelector(this.form.dataset.paginationBottom)
      : null;

    if (data.paginationTop && top) {
      top.innerHTML = data.paginationTop;
    }

    if (data.paginationBottom && bottom) {
      bottom.innerHTML = data.paginationBottom;
    }

    if (data.pagination) {
      if (top) top.innerHTML = data.pagination;
      if (bottom) bottom.innerHTML = data.pagination;
    }
  }

  renderFiltersSummary(data) {
    if (!data.filtersSummary) return;

    const target = document.querySelector(
      this.form.dataset.filtersTarget || "#filters-summary"
    );

    if (target) {
      target.innerHTML = data.filtersSummary;
    }
  }
}