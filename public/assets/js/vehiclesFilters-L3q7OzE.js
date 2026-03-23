// vehiclesFilterSimple.js
document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("#filters-form");
  if (!form) return;

  const resultsTarget = document.querySelector(
    "[data-target='vehicles-search-results']"
  );
  const paginationTopTarget = document.querySelector(
    "[data-target='pagination-top']"
  );
  const paginationBottomTarget = document.querySelector(
    "[data-target='pagination-bottom']"
  );
  const fetchUrl = form.dataset.fetchUrl;

  async function submitFilters(page = 1) {
    const formData = new FormData(form);
    const filters = {};

    formData.forEach((val, key) => {
      const cleanKey = key.replace(/\[\]$/, "");
      if (filters[cleanKey])
        filters[cleanKey] = [].concat(filters[cleanKey], val);
      else filters[cleanKey] = [val];
    });

    try {
      const res = await fetch(`${fetchUrl}?page=${page}`, {
        method: "POST",
        body: JSON.stringify({ filters }),
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      const data = await res.json();

      if (resultsTarget) resultsTarget.innerHTML = data.results;
      if (paginationTopTarget)
        paginationTopTarget.innerHTML = data.paginationTop;
      if (paginationBottomTarget)
        paginationBottomTarget.innerHTML = data.paginationBottom;
    } catch (e) {
      console.error("AJAX error", e);
    }
  }

  // Événements
  form.addEventListener("change", () => submitFilters(1));

  document.addEventListener("click", e => {
    const link = e.target.closest("[data-page]");
    if (link) {
      e.preventDefault();
      const page = parseInt(link.dataset.page);
      if (!isNaN(page)) submitFilters(page);
    }
  });
});
