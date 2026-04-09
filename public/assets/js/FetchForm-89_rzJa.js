// assets/js/FetchForm.js
// ================================
// Classe ES6 pour gérer les formulaires fetch / autocomplete / AJAX
// ================================

export class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) {
      throw new Error("FetchForm: argument doit être un <form>");
    }

    this.form = form;
    this.input = form.querySelector('input[type="text"]');
    this.resultDivId = form.dataset.resultDiv || "results";
    this.resultContainer = document.getElementById(this.resultDivId);
    this.fetchUrl = form.dataset.fetchUrl || form.action;
    this.itemUrlTemplate = form.dataset.itemUrl || "";
    this.resultLinks = form.dataset.resultLinks === "true";
    this.ajaxModal = form.dataset.ajaxModal === "true";
    this.debounceTime = parseInt(form.dataset.debounce) || 300;

    this.itemClass = form.dataset.itemClass || "vehicle-item";
    this.linkClass = form.dataset.linkClass || "vehicle-link";
    this.noResultsClass = form.dataset.noResultsClass || "dropdown-no-results";

    if (!this.resultContainer) {
      console.warn(`[FetchForm] Container #${this.resultDivId} introuvable`);
      return;
    }

    this.timeout = null;

    this.init();
  }

  init() {
    if (this.input && this.input.dataset.autocomplete === "true") {
      this.input.addEventListener("input", () => this.onInput());
    }

    if (this.form.dataset.preventSubmit === "true") {
      this.form.addEventListener("submit", e => e.preventDefault());
    }
  }

  onInput() {
    clearTimeout(this.timeout);
    const query = this.input.value.trim();
    if (query.length === 0) {
      this.clearResults();
      return;
    }
    this.timeout = setTimeout(
      () => this.fetchResults(query),
      this.debounceTime
    );
  }

  fetchResults(query) {
    fetch(`${this.fetchUrl}?q=${encodeURIComponent(query)}`, {
      headers: { "X-Requested-With": "XMLHttpRequest" }
    })
      .then(r => r.json())
      .then(data => this.handleResponse(data))
      .catch(err => console.error("[FetchForm] Erreur fetch:", err));
  }

  handleResponse(data) {
    if (data.items) {
      this.renderItems(data.items);
    } else if (data.results) {
      // rendu HTML côté serveur
      this.resultContainer.innerHTML = data.results;
    } else {
      this.clearResults();
    }
  }

  renderItems(items) {
    this.resultContainer.innerHTML = "";
    if (!items.length) {
      const div = document.createElement("div");
      div.className = this.noResultsClass;
      div.textContent = "Aucun résultat trouvé";
      this.resultContainer.appendChild(div);
      return;
    }

    items.forEach(item => {
      if (this.resultLinks) {
        const link = document.createElement("a");
        link.href = this.itemUrlTemplate.replace("ID_PLACEHOLDER", item.id);
        link.className = this.linkClass;
        link.textContent =
          item.label || item.name || item.registrationNumber || `ID ${item.id}`;

        if (this.ajaxModal) {
          link.addEventListener("click", e => {
            e.preventDefault();
            this.openModal(link.href);
          });
        }

        this.resultContainer.appendChild(link);
      } else {
        const div = document.createElement("div");
        div.className = this.itemClass;
        div.textContent =
          item.label || item.name || item.registrationNumber || `ID ${item.id}`;
        this.resultContainer.appendChild(div);
      }
    });
  }

  clearResults() {
    this.resultContainer.innerHTML = "";
  }

  openModal(url) {
    fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } })
      .then(r => r.text())
      .then(html => {
        let modal = document.getElementById("modal");
        if (!modal) {
          modal = document.createElement("div");
          modal.id = "modal";
          modal.style.position = "fixed";
          modal.style.inset = "0";
          modal.style.display = "flex";
          modal.style.alignItems = "center";
          modal.style.justifyContent = "center";
          modal.style.backgroundColor = "rgba(0,0,0,0.5)";
          document.body.appendChild(modal);
        }
        modal.innerHTML = html;
        modal.style.display = "flex";
      });
  }

  // Méthode statique pour initialiser tous les fetch forms sur la page
  static initAll(selector = "form[data-fetch-form]") {
    const forms = document.querySelectorAll(selector);
    return Array.from(forms).map(f => new FetchForm(f));
  }
}

// Initialisation automatique si le script est chargé dans le navigateur
document.addEventListener("DOMContentLoaded", () => {
  FetchForm.initAll();
});
