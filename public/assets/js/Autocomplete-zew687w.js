export default class Autocomplete {
  constructor(input) {
    if (!(input instanceof HTMLInputElement)) return;

    this.input = input;
    this.url = input.dataset.url;
    this.dropdownTarget = document.querySelector(input.dataset.target);
    this.resultsContainer = input.dataset.resultsContainer
      ? document.querySelector(input.dataset.resultsContainer)
      : null;
    this.itemUrlField = input.dataset.itemUrl || "url";
    this.currentPage = 1;
    this.hasMore = true;

    if (!this.url || !this.dropdownTarget) {
      console.warn("Autocomplete: configuration manquante", input);
      return;
    }

    this.init();
  }

  init() {
    console.log("Autocomplete initialisé");

    let debounce = null;

    this.input.addEventListener("input", e => {
      const value = e.target.value.trim();
      clearTimeout(debounce);

      if (value.length < 2) {
        this.dropdownTarget.innerHTML = "";
        this.currentPage = 1;
        this.hasMore = true;
        return;
      }

      debounce = setTimeout(() => {
        this.currentPage = 1;
        this.hasMore = true;
        this.fetch(value, this.currentPage, true); // reset results
      }, 250);
    });

    // Pagination via scroll
    this.dropdownTarget.addEventListener("scroll", () => {
      if (!this.hasMore) return;

      const { scrollTop, scrollHeight, clientHeight } = this.dropdownTarget;
      if (scrollTop + clientHeight >= scrollHeight - 10) {
        // marge 10px
        this.currentPage++;
        this.fetch(this.input.value.trim(), this.currentPage, false);
      }
    });

    // Click pagination cards (optionnel)
    if (this.resultsContainer) {
      this.resultsContainer.addEventListener("click", e => {
        const link = e.target.closest("a[data-page]");
        if (link) {
          e.preventDefault();
          const page = parseInt(link.dataset.page, 10) || 1;
          this.fetch(this.input.value.trim(), page, true);
        }
      });
    }
  }

  async fetch(query, page = 1, reset = false) {
    if (!query || query.length < 2) return;

    try {
      const res = await fetch(
        `${this.url}?q=${encodeURIComponent(
          query
        )}&autocomplete=true&page=${page}`
      );
      const data = await res.json();

      // --- Dropdown ---
      if (Array.isArray(data.items)) {
        let html = reset
          ? "<ul class='autocomplete-list'>"
          : this.dropdownTarget.querySelector("ul")?.outerHTML ||
            "<ul class='autocomplete-list'>";
        data.items.forEach(item => {
          const li = `<li>${
            item.url
              ? `<a href="${item.url}" class="autocomplete-item">${item.label}</a>`
              : `<div class="autocomplete-item">${item.label}</div>`
          }</li>`;
          if (reset) html += li;
          else
            this.dropdownTarget
              .querySelector("ul")
              .insertAdjacentHTML("beforeend", li);
        });
        if (reset) html += "</ul>";
        if (reset) this.dropdownTarget.innerHTML = html;
      } else {
        this.dropdownTarget.innerHTML = "";
      }

      // --- Cards (seulement page 1) ---
      if (this.resultsContainer && page === 1 && data.results) {
        this.resultsContainer.innerHTML = data.results;

        const paginationTopTarget = this.resultsContainer.dataset.paginationTop
          ? document.querySelector(this.resultsContainer.dataset.paginationTop)
          : null;
        if (paginationTopTarget && data.paginationTop)
          paginationTopTarget.innerHTML = data.paginationTop;

        const paginationBottomTarget = this.resultsContainer.dataset
          .paginationBottom
          ? document.querySelector(
              this.resultsContainer.dataset.paginationBottom
            )
          : null;
        if (paginationBottomTarget && data.paginationBottom)
          paginationBottomTarget.innerHTML = data.paginationBottom;
      }

      // Marque si plus de résultats pour le scroll
      if (data.items.length === 0) this.hasMore = false;
    } catch (e) {
      console.error("Autocomplete error:", e);
      this.dropdownTarget.innerHTML = "";
    }
  }
}
