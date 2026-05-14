export default class FetchForm {
  constructor(form) {
    // Sécurisation : uniquement formulaire HTML
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;

    // État de requête
    this.isLoading = false;
    this.abortController = new AbortController();

    this.init();
  }

  init() {
    console.log("INIT FetchForm", this.form);

    // Submit manuel (bouton ou enter)
    this.form.addEventListener("submit", async e => {
      e.preventDefault();
      e.stopPropagation();

      await this.send();
    });

    // Change avec debounce pour éviter les appels multiples (sliders, checkboxes, pagination)
    let changeTimeout = null;

    this.form.addEventListener("change", () => {
      clearTimeout(changeTimeout);

      changeTimeout = setTimeout(() => {
        this.send();
      }, 100);
    });
  }

  warnMissingDataset(key, value) {
    if (!value) {
      console.warn(`[FetchForm] Missing dataset: "${key}"`, this.form);
      return false;
    }
    return true;
  }

  resolveTarget(selector, name) {
    if (!selector) {
      console.warn(`[FetchForm] Missing dataset for ${name}`);
      return null;
    }

    const el = document.querySelector(selector);

    if (!el) {
      console.warn(`[FetchForm] Target not found for ${name}: ${selector}`);
    }

    return el;
  }

  async send() {
    // Annule la requête précédente si elle existe
    if (this.abortController) {
      this.abortController.abort();
    }

    // Nouveau contrôleur pour la requête en cours
    this.abortController = new AbortController();

    // État loading
    if (this.isLoading) return;

    this.isLoading = true;
    this.form.dataset.loading = "1";

    const url = this.form.dataset.fetchUrl;
    const mode = this.form.dataset.fetchMode || "json";

    if (!this.warnMissingDataset("fetchUrl", url)) return;

    const target = this.resolveTarget(this.form.dataset.target, "target");
    const filtersTarget = this.resolveTarget(this.form.dataset.filtersTarget, "filtersTarget");
    const paginationTop = this.resolveTarget(this.form.dataset.paginationTop, "paginationTop");
    const paginationBottom = this.resolveTarget(
      this.form.dataset.paginationBottom,
      "paginationBottom"
    );

    if (!target) {
      console.error("[FetchForm] Missing target container");
      return;
    }

    try {
      const formData = new FormData(this.form);
      const params = new URLSearchParams(formData);

      const requestUrl = `${url}?${params.toString()}`;

      const res = await fetch(requestUrl, {
        method: "GET",
        signal: this.abortController.signal
      });

      if (!res.ok) {
        console.error("[FetchForm] HTTP error", res.status);
        return;
      }

      // Mode HTML
      if (mode === "html") {
        const html = await res.text();
        target.innerHTML = html;

        window.dispatchEvent(new Event("ui:updated"));
        return;
      }

      const data = await res.json();

      // Liste principale
      if (data.list) {
        target.innerHTML = data.list;
      }

      // Pagination haut
      if (data.pagination_top && paginationTop) {
        paginationTop.innerHTML = data.pagination_top;
      }

      // Pagination bas
      if (data.pagination_bottom && paginationBottom) {
        paginationBottom.innerHTML = data.pagination_bottom;
      }

      // Résumé filtres
      if (data.filtersSummary) {
        const summary = document.querySelector("#filters-summary");

        if (summary) {
          summary.innerHTML = data.filtersSummary;
        }
      }

      window.dispatchEvent(new Event("ui:updated"));
    } catch (err) {
      if (err.name === "AbortError") return;

      console.error("[FetchForm] erreur AJAX", err);
    } finally {
      this.isLoading = false;
      delete this.form.dataset.loading;
    }
  }
}
