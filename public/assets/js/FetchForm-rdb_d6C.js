export default class FetchForm {
  constructor(form) {
    // Sécurisation : uniquement formulaire HTML
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;

    // Anti double requête
    this.isLoading = false;

    this.init();
  }

  init() {
    console.log("INIT FetchForm", this.form);

    this.form.addEventListener("submit", async e => {
      e.preventDefault();
      await this.send();
    });

    // IMPORTANT : capture sur tous les inputs
    this.form.addEventListener("input", () => {
      this.send();
    });

    // fallback pour checkbox + custom events
    this.form.addEventListener("change", () => {
      this.send();
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
    // Anti double appel
    if (this.isLoading) return;

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

    this.isLoading = true;

    try {
      const formData = new FormData(this.form);
      const params = new URLSearchParams(formData);

      const requestUrl = `${url}?${params.toString()}`;

      const res = await fetch(requestUrl, {
        method: "GET",
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      if (!res.ok) {
        console.error("[FetchForm] HTTP error", res.status);
        return;
      }

      // MODE HTML
      if (mode === "html") {
        const html = await res.text();
        target.innerHTML = html;

        window.dispatchEvent(new Event("ui:updated"));
        return;
      }

      const data = await res.json();

      // LISTE PRINCIPALE
      if (data.list) {
        target.innerHTML = data.list;
      }

      // PAGINATION TOP
      if (paginationTop && data.pagination_top !== undefined) {
        paginationTop.innerHTML = data.pagination_top;
      }

      // PAGINATION BOTTOM
      if (paginationBottom && data.pagination_bottom !== undefined) {
        paginationBottom.innerHTML = data.pagination_bottom;
      }

      // FILTRES DYNAMIQUES
      if (filtersTarget && data.filters) {
        filtersTarget.innerHTML = data.filters;
      }

      // BADGES FILTRES
      if (data.filtersSummary) {
        const summary = document.querySelector("#filters-summary");
        if (summary) summary.innerHTML = data.filtersSummary;
      }

      window.dispatchEvent(new Event("ui:updated"));
    } catch (err) {
      console.error("[FetchForm] erreur AJAX", err);
    } finally {
      this.isLoading = false;
    }
  }
}
