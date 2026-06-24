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
    // changement de filtre ou champ → reset page + debounce fetch
    this.form.addEventListener("change", e => {
      if (!this.form.contains(e.target)) return;

      this.resetPage();
      this.scheduleSend();
    });

    // saisie texte → reset page + debounce fetch
    this.form.addEventListener("input", e => {
      if (!this.form.contains(e.target)) return;

      this.resetPage();
      this.scheduleSend();
    });

    // pagination via data-page
    this.form.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;

      e.preventDefault();

      this.goToPage(btn.dataset.page);
    });

    // bouton reset filtres
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

  /**
   * Force la page à 1
   */
  resetPage() {
    this.setPage(1);
  }

  /**
   * Définit la page courante dans le form
   */
  setPage(page) {
    let input = this.form.querySelector('[name="page"]');

    if (!input) {
      input = document.createElement("input");
      input.type = "hidden";
      input.name = "page";
      this.form.appendChild(input);
    }

    input.value = page;
  }

  /**
   * Navigation pagination
   */
  goToPage(page) {
    this.setPage(page);
    this.send();
  }

  /**
   * Debounce des requêtes
   */
  scheduleSend() {
    clearTimeout(this._timeout);

    this._timeout = setTimeout(() => {
      this.send();
    }, 120);
  }

  /**
   * Envoi AJAX principal
   */
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

      this.renderResults(target, data);
      this.renderPagination(data);
    } catch (e) {
      if (e.name !== "AbortError") {
        console.error("[FetchForm]", e);
      }
    }
  }

  /**
   * Injection résultats
   */
  renderResults(target, data) {
    if (data.results) {
      target.innerHTML = data.results;
      return;
    }

    if (data.list) {
      target.innerHTML = data.list;
    }
  }

  /**
   * Rendu pagination basé sur STATE (pas HTML backend)
   */
  renderPagination(data) {
    const current = Number(data.page || 1);
    const max = Number(data.maxPage || 1);
    const count = data.count ?? null;

    const counter = document.querySelector("[data-pagination-counter]");
    if (counter && count !== null) {
      counter.textContent = count;
    }

    const allPrev = document.querySelectorAll("[data-page-prev]");
    const allNext = document.querySelectorAll("[data-page-next]");
    const allPages = document.querySelectorAll("[data-page]");

    allPrev.forEach(btn => {
      btn.disabled = current <= 1;
      btn.dataset.page = Math.max(1, current - 1);
    });

    allNext.forEach(btn => {
      btn.disabled = current >= max;
      btn.dataset.page = Math.min(max, current + 1);
    });

    allPages.forEach(btn => {
      const p = Number(btn.dataset.page);
      btn.classList.toggle("active", p === current);
    });
  }
}
