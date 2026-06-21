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

    this.lastTrigger = null;

    this.init();
  }

  init() {
    // submit (pagination ou recherche directe)
    this.form.addEventListener("submit", e => {
      e.preventDefault();

      this.lastTrigger = "submit";

      this.send();
    });

    // filtres select / checkbox / etc
    document.addEventListener("change", e => {
      if (!this.isReady) return;
      if (!this.form.contains(e.target)) return;

      this.lastTrigger = "filter";

      this.scheduleSend();
    });

    // filtres input (search, sliders)
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

  /**
   * reset page uniquement si on est sur un changement de filtre
   * IMPORTANT: ne pas impacter la pagination
   */
  resetPageIfFilter() {
    if (this.lastTrigger !== "filter") {
      return;
    }

    const pageInput = this.form.querySelector('[name="page"]');

    if (pageInput) {
      pageInput.value = 1;
    }
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

    // sécurité pagination vs filtres
    this.resetPageIfFilter();

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

      // résultats
      if (data.results) {
        target.innerHTML = data.results;
      } else if (data.list) {
        target.innerHTML = data.list;
      }

      // pagination top
      const paginationTopSelector = this.form.dataset.paginationTop;

      if (paginationTopSelector && data.paginationTop) {
        const top = document.querySelector(paginationTopSelector);
        if (top) top.innerHTML = data.paginationTop;
      }

      // pagination bottom
      const paginationBottomSelector = this.form.dataset.paginationBottom;

      if (paginationBottomSelector && data.paginationBottom) {
        const bottom = document.querySelector(paginationBottomSelector);
        if (bottom) bottom.innerHTML = data.paginationBottom;
      }

      // fallback legacy pagination
      if (data.pagination) {
        const top = paginationTopSelector ? document.querySelector(paginationTopSelector) : null;

        const bottom = paginationBottomSelector
          ? document.querySelector(paginationBottomSelector)
          : null;

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

      // refresh UI global
      window.dispatchEvent(new Event("ui:updated"));

      // badges frontend
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
