export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.isLoading = false;

    this.init();
  }

  init() {
    this.form.addEventListener("submit", async e => {
      e.preventDefault();
      e.stopPropagation();

      await this.send();
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
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl;
    const mode = this.form.dataset.fetchMode || "html";

    if (!this.warnMissingDataset("fetchUrl", url)) return;

    const targetSelector = this.form.dataset.target;
    const filtersSelector = this.form.dataset.filtersTarget;
    const paginationTopSelector = this.form.dataset.paginationTop;
    const paginationBottomSelector = this.form.dataset.paginationBottom;

    const target = this.resolveTarget(targetSelector, "target");
    const filtersTarget = this.resolveTarget(filtersSelector, "filtersTarget");
    const paginationTop = this.resolveTarget(
      paginationTopSelector,
      "paginationTop"
    );
    const paginationBottom = this.resolveTarget(
      paginationBottomSelector,
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

    console.log("RAW FORM DATA:");
console.log([...new FormData(this.form).entries()]);

    const params = new URLSearchParams(new FormData(this.form));

    fetch(url, {
      method: "POST",
      body: params
    })
      .then(res => res.json())
      .then(data => {
        console.log("AJAX RESPONSE FULL:", data);
        console.log("filtersSummary:", data.filtersSummary);

        // Liste véhicules
        if (data.list) {
          target.innerHTML = data.list;
        }

        // Pagination top
        if (data.pagination_top && paginationTop) {
          paginationTop.innerHTML = data.pagination_top;
        }

        // Pagination bottom
        if (data.pagination_bottom && paginationBottom) {
          paginationBottom.innerHTML = data.pagination_bottom;
        }

        // Afficher les badges de filtres dynamiques
        if (data.filtersSummary) {
          const summary = document.querySelector("#filters-summary");

          if (summary) {
            summary.innerHTML = data.filtersSummary;
          }
        }
        // Re-init UI après remplacement DOM
        window.dispatchEvent(new Event("ui:updated"));
      })
      .catch(err => {
        console.error("[FetchForm] erreur AJAX", err);
      })
      .finally(() => {
        this.isLoading = false;
      });

      if (mode === "html") {
        const html = await res.text();
        target.innerHTML = html;

        window.dispatchEvent(new Event("ui:updated"));
        return;
      }

      const data = await res.json();

      if (data.list) {
        target.innerHTML = data.list;
      } else {
        console.warn("[FetchForm] Missing list in response");
      }

      if (filtersTarget && data.filters) {
        filtersTarget.innerHTML = data.filters;
      }

      if (paginationTop && data.pagination_top) {
        paginationTop.innerHTML = data.pagination_top;
      }

      if (paginationBottom && data.pagination_bottom) {
        paginationBottom.innerHTML = data.pagination_bottom;
      }

      window.dispatchEvent(new Event("ui:updated"));
    } catch (e) {
      console.error("[FetchForm] Error:", e);
    } finally {
      this.isLoading = false;
    }
  }
}
