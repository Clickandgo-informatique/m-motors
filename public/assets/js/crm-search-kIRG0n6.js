function initCrmSearch(context = document) {
    function bindSearch(input) {
        if (!input || input.dataset.crmBound === "1") {
            return;
        }

        input.dataset.crmBound = "1";

        const url = input.dataset.autocompleteUrl;
        if (!url) return;

        const wrapper = document.createElement("div");
        wrapper.classList.add("crm-dropdown-wrapper");
        wrapper.style.position = "relative";

        input.parentNode.style.position = "relative";
        input.parentNode.appendChild(wrapper);

        const dropdown = document.createElement("div");
        dropdown.classList.add("crm-dropdown");
        wrapper.appendChild(dropdown);

        let timeout = null;

        input.addEventListener("input", () => {
            clearTimeout(timeout);

            const value = input.value;

            if (value.length < 2) {
                dropdown.innerHTML = "";
                return;
            }

            timeout = setTimeout(() => {
                fetch(url + "?q=" + encodeURIComponent(value))
                    .then(res => res.json())
                    .then(data => {
                        dropdown.innerHTML = "";

                        data.forEach(item => {
                            const el = document.createElement("div");
                            el.classList.add("crm-item");
                            el.textContent = item.text;

                            el.addEventListener("click", () => {
                                input.value = item.text;

                                // IMPORTANT CRM: stocke l'ID réel
                                input.dataset.selectedId = item.id;

                                dropdown.innerHTML = "";
                            });

                            dropdown.appendChild(el);
                        });
                    });
            }, 200);
        });
    }

    const inputs = context.querySelectorAll(".js-customer-search, .js-vehicle-search");

    inputs.forEach(bindSearch);
}

/**
 * AssetMapper safe init
 * - first load
 * - turbo / modal reload safe
 */
initCrmSearch();

document.addEventListener("turbo:load", () => initCrmSearch());
document.addEventListener("DOMContentLoaded", () => initCrmSearch());
