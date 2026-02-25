console.log("FetchForm.js instancié");
export default class FetchForm {
  constructor(input) {
    this.input = input;

    // Le formulaire parent
    this.form = input.closest("form");

    // L’endpoint vient du form
    this.endpoint = this.form.dataset.searchForm;

    // Le conteneur des résultats
    this.resultDivSelector = input.dataset.resultDiv;
    this.resultDiv = document.querySelector(this.resultDivSelector);
    console.log("Result div :", this.resultDiv);

    // Options
    this.autocomplete = input.dataset.autocomplete === "true";
    this.defaultSuggestions = input.dataset.defaultSuggestions === "true";
    this.highlight = input.dataset.highlight === "true";

    this.paginationEnabled = this.resultDiv.dataset.pagination === "ajax";
    this.paginationMode = this.resultDiv.dataset.paginationMode || "load-more";
    this.paginationLimit = parseInt(
      this.resultDiv.dataset.paginationLimit || "10",
      10
    );

    if (!this.endpoint || !this.resultDiv) {
      console.warn("FetchForm: configuration incomplète", input);
      return;
    }

    this.bindEvents();
  }

  bindEvents() {
    this.input.addEventListener("input", () => {
      const q = this.input.value.trim();

      if (!q && !this.defaultSuggestions) {
        this.clearResults();
        return;
      }

      this.search(q);
    });
  }

  async search(q, offset = 0) {
    // On marque qu'une recherche est en cours
    this.hasSearched = true;

    if (offset === 0 && q.length > 0) {
      this.resultDiv.innerHTML = "";
    }

    const url = `${this.endpoint}?${this.input.name}=${encodeURIComponent(
      q
    )}&offset=${offset}&limit=${this.paginationLimit}`;

    try {
      const response = await fetch(url);
      const data = await response.json();
      this.renderResults(data, q);
    } catch (e) {
      console.error("FetchForm error:", e);
    }
  }

  renderResults(data, q) {
    if (!data.length) {
      this.resultDiv.innerHTML = '<div class="no-results">Aucun résultat</div>';
      return;
    }

    data.forEach(item => {
      const div = document.createElement("div");
      div.classList.add("result-item");

      let text = Object.values(item)
        .filter(v => typeof v === "string")
        .join(" ");

      if (this.highlight && q) {
        const regex = new RegExp(`(${q})`, "gi");
        text = text.replace(regex, "<mark>$1</mark>");
      }

      // 🔥 Version avec lien HTML classique
      div.innerHTML = `
            <a href="/vehicles/models/${item.id}/edit" class="result-link">
                ${text}
            </a>
        `;

      this.resultDiv.appendChild(div);
    });
  }

  clearResults() {
    // On n'efface que si on a déjà fait une recherche
    if (this.hasSearched) {
      this.resultDiv.innerHTML = "";
    }
  }
}
