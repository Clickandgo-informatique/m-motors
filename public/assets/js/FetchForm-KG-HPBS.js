document.addEventListener("DOMContentLoaded", () => {
  const forms = document.querySelectorAll("form[data-search-form]");

  forms.forEach(form => {
    const input = form.querySelector('input[type="text"]');
    const resultDiv = document.getElementById(form.dataset.resultDiv);
    let timeout;

    // Fonction de recherche AJAX
    const search = () => {
      const query = input.value.trim();
      if (!query) {
        resultDiv.innerHTML = "";
        return;
      }

      clearTimeout(timeout);
      timeout = setTimeout(() => {
        fetch(form.dataset.searchForm + "?q=" + encodeURIComponent(query))
          .then(resp => resp.json())
          .then(payload => {
            renderResults(payload);
          })
          .catch(err => console.error("FetchForm search error", err));
      }, 250); // debounce 250ms
    };

    // Fonction pour rendre les résultats
    const renderResults = payload => {
      resultDiv.innerHTML = ""; // vider avant
      const items = payload.items || [];

      if (!Array.isArray(items) || !items.length) {
        const div = document.createElement("div");
        div.classList.add(form.dataset.noResultsClass || "dropdown-no-results");
        div.textContent = "Aucun véhicule trouvé";
        resultDiv.appendChild(div);
        return;
      }

      items.forEach(item => {
        const div = document.createElement("div");
        div.classList.add(form.dataset.itemClass || "vehicle-item");

        if (form.dataset.resultLinks === "true") {
          const a = document.createElement("a");
          a.href = item.url;
          a.textContent = item.label;
          if (form.dataset.ajaxModal === "true") {
            a.dataset.ajaxModal = true;
          }
          a.classList.add(form.dataset.linkClass || "vehicle-link");
          div.appendChild(a);
        } else {
          div.textContent = item.label;
        }

        resultDiv.appendChild(div);
      });
    };

    // Événement input
    input.addEventListener("input", search);

    // Optionnel : bouton toggle recherche
    const btn = form.querySelector("[data-search-toggle]");
    if (btn) btn.addEventListener("click", search);
  });
});
