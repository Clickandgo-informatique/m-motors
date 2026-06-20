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

    this.init();
  }

  init() {
    // Submit classique intercepté
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    /**
     * IMPORTANT FIX :
     * On ne dépend plus du form.change (cassé par DOM Symfony / render controller)
     * On écoute globalement et on filtre via form.contains()
     */
    document.addEventListener("change", e => {
      if (!this.isReady) return;
      if (!this.form.contains(e.target)) return;

      this.scheduleSend();
    });

    /**
     * Même logique pour input (sliders / champs texte rapides)
     */
    document.addEventListener("input", e => {
      if (!this.isReady) return;
      if (!this.form.contains(e.target)) return;

      this.scheduleSend();
    });

    // petit délai pour éviter les triggers init
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

    // annule requête précédente
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
      // construction params GET
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

      // fallback pagination legacy
      if (data.pagination) {
        const top = paginationTopSelector ? document.querySelector(paginationTopSelector) : null;

        const bottom = paginationBottomSelector
          ? document.querySelector(paginationBottomSelector)
          : null;

        if (top) top.innerHTML = data.pagination;
        if (bottom) bottom.innerHTML = data.pagination;
      }

      // filters summary / badges backend
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

      // badges frontend (si présent)
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
