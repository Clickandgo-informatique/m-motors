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
            if (!btn) return;

            const badge = btn.closest(".badge-filter");
            if (!badge) return;

            const filter = badge.dataset.filter;
            const value = badge.dataset.value;

            const checkboxes = this.form.querySelectorAll(`input[name="filters[${filter}][]"]`);

            checkboxes.forEach(cb => {
                if (cb.value === value) {
                    cb.checked = false;
                }
            });

            if (["mileage", "year", "price"].includes(filter)) {
                this.resetSlider(filter);
            }

            badge.remove();

            if (typeof this.onRemoveCallback === "function") {
                this.onRemoveCallback();
            }
        });
    }

    resetSlider(filter) {
        const sliderEl = this.form.querySelector(`.double-slider[data-filter="${filter}"]`);

        if (!sliderEl) return;

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

        if (inputMin) inputMin.value = defaultMin;
        if (inputMax) inputMax.value = defaultMax;

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
        if (!this.container || !this.form) return;

        this.container.innerHTML = "";

        const formData = new FormData(this.form);

        const rangeFilters = {
            mileage: ["filters[mileageMin]", "filters[mileageMax]"],
            year: ["filters[registrationYearMin]", "filters[registrationYearMax]"],
            price: ["filters[priceMin]", "filters[priceMax]"]
        };

        for (const [filter, [minKey, maxKey]] of Object.entries(rangeFilters)) {
            const min = formData.get(minKey);
            const max = formData.get(maxKey);

            if (min !== "" && max !== "") {
                this.createBadge(filter, `${min}-${max}`);
            }
        }

        for (const [key, value] of formData.entries()) {
            const match = key.match(/^filters\[(.+?)\](\[\])?$/);
            if (!match) continue;

            const name = match[1];
            const isArray = !!match[2];

            if (!isArray || !value) continue;

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

        const isRange = ["year", "mileage", "price"].includes(filter);

        badge.className = isRange ? "badge-filter badge-filter-range" : "badge-filter";

        badge.dataset.filter = filter;
        badge.dataset.value = value;

        if (!label) {
            label = value;
        }

        const text = document.createElement("span");
        text.textContent = label;

        badge.appendChild(text);

        if (!isRange) {
            const removeBtn = document.createElement("button");
            removeBtn.type = "button";
            removeBtn.className = "badge-remove";
            removeBtn.textContent = "×";
            badge.appendChild(removeBtn);
        }

        this.container.appendChild(badge);
    }
}
