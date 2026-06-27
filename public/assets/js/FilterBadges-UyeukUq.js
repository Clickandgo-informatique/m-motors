export default class FilterBadges {
    constructor(container, form, onRemoveCallback) {
        this.container = container;
        this.form = form;
        this.onRemoveCallback = onRemoveCallback;

        if (!this.container || !this.form) return;

        this.bindEvents();
    }

    bindEvents() {
        this.container.addEventListener("click", e => {
            const btn = e.target.closest(".badge-remove");

            if (!btn) {
                return;
            }

            const badge = btn.closest(".badge-filter");

            if (!badge) {
                return;
            }

            const filter = badge.dataset.filter;
            const value = badge.dataset.value;

            const checkboxes = this.form.querySelectorAll(`input[name="filters[${filter}][]"]`);

            if (checkboxes.length) {
                checkboxes.forEach(cb => {
                    if (cb.value === value) {
                        cb.checked = false;
                    }
                });
            }

            if (filter === "mileage" || filter === "year" || filter === "price") {
                this.resetSlider(filter);
            }

            badge.remove();

            if (typeof this.onRemoveCallback === "function") {
                this.onRemoveCallback();
            } else {
                this.form.dispatchEvent(
                    new Event("submit", {
                        bubbles: true,
                        cancelable: true
                    })
                );
            }
        });
    }

    resetSlider(filter) {
        const sliderEl = this.form.querySelector(`.double-slider[data-filter="${filter}"]`);

        if (!sliderEl) {
            return;
        }

        const defaultMin = parseInt(sliderEl.dataset.min, 10);
        const defaultMax = parseInt(sliderEl.dataset.max, 10);

        const map = {
            year: "registrationYear",
            mileage: "mileage",
            price: "price"
        };

        const base = map[filter] || filter;

        const inputMin = this.form.querySelector(`input[name="filters[${base}Min]"]`);

        const inputMax = this.form.querySelector(`input[name="filters[${base}Max]"]`);

        if (inputMin) {
            inputMin.value = defaultMin;
        }

        if (inputMax) {
            inputMax.value = defaultMax;
        }

        sliderEl.dispatchEvent(
            new CustomEvent("sliderChanged", {
                bubbles: true,
                detail: {
                    filter,
                    min: defaultMin,
                    max: defaultMax
                }
            })
        );
    }

    updateBadges() {
        if (!this.container || !this.form) {
            return;
        }

        // Ne supprimer QUE les badges JS (ceux créés par FilterBadges)
        this.container.querySelectorAll(".badge-filter").forEach(b => b.remove());

        const formData = new FormData(this.form);

        const rangeFilters = {
            mileage: ["filters[mileageMin]", "filters[mileageMax]"],
            year: ["filters[registrationYearMin]", "filters[registrationYearMax]"],
            price: ["filters[priceMin]", "filters[priceMax]"]
        };

        // Gestion des sliders
        for (const [filter, [minKey, maxKey]] of Object.entries(rangeFilters)) {
            const min = formData.get(minKey);
            const max = formData.get(maxKey);

            if (min === null || max === null || min === "" || max === "") {
                continue;
            }
            this.createBadge(filter, `${min}-${max}`);   

        }

        // Gestion des filtres multiples (checkbox)
        for (const [key, value] of formData.entries()) {
            const match = key.match(/^filters\[(.+?)\](\[\])?$/);

            if (!match) {
                continue;
            }

            const name = match[1];
            const isArray = !!match[2];

            if (!isArray || !value) {
                continue;
            }

            const input = this.form.querySelector(
                `input[name="filters[${name}][]"][value="${value}"]`
            );

            const label = input
                ? this.form.querySelector(`label[for="${input.id}"]`)?.textContent
                : value;

            this.createBadge(name, value, label);
        }
    }

    createBadge(filter, value, label = null) {
        const badge = document.createElement("span");

        const isRangeBadge = filter === "year" || filter === "mileage" || filter === "price";

        badge.className = isRangeBadge
            ? "badge badge-filter badge-filter-range"
            : "badge badge-filter";

        badge.dataset.filter = filter;
        badge.dataset.value = value;

        if (!label) {
            if (filter === "year") {
                const [min, max] = value.split("-");

                label = `Années : ${min} → ${max}`;
            } else if (filter === "mileage") {
                const [min, max] = value.split("-");

                label =
                    `Kilométrage : ${Number(min).toLocaleString("fr-FR")} → ` +
                    `${Number(max).toLocaleString("fr-FR")} km`;
            } else if (filter === "price") {
                const [min, max] = value.split("-");

                label =
                    `Prix : ${Number(min).toLocaleString("fr-FR")} € → ` +
                    `${Number(max).toLocaleString("fr-FR")} €`;
            } else {
                label = value;
            }
        }

        const text = document.createElement("span");
        text.textContent = label;

        badge.appendChild(text);

        if (!isRangeBadge) {
            const removeBtn = document.createElement("button");

            removeBtn.type = "button";
            removeBtn.className = "badge-remove";
            removeBtn.textContent = "×";

            badge.appendChild(removeBtn);
        }

        this.container.appendChild(badge);
    }
}
