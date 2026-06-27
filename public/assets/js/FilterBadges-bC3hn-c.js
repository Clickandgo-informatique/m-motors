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

        if (typeof this.onRemoveCallback === "function") {
            this.onRemoveCallback();
        }
    }

    updateBadges() {
        if (!this.container || !this.form) {
            return;
        }

        this.container.innerHTML = "";

        this.renderRangeBadges();
        this.renderCheckboxBadges();
    }

    renderRangeBadges() {
        const rangeFilters = {
            mileage: ["filters[mileageMin]", "filters[mileageMax]"],
            year: ["filters[registrationYearMin]", "filters[registrationYearMax]"],
            price: ["filters[priceMin]", "filters[priceMax]"]
        };

        for (const [filter, [minKey, maxKey]] of Object.entries(rangeFilters)) {
            const inputMin = this.form.querySelector(`input[name="${minKey}"]`);
            const inputMax = this.form.querySelector(`input[name="${maxKey}"]`);

            const sliderEl = this.form.querySelector(`.double-slider[data-filter="${filter}"]`);

            if (!inputMin || !inputMax || !sliderEl) {
                continue;
            }

            const min = inputMin.value;
            const max = inputMax.value;

            const defaultMin = sliderEl.dataset.min;
            const defaultMax = sliderEl.dataset.max;

            const label = this.formatRangeLabel(filter, min, max);

            this.createBadge(filter, `${min}-${max}`, label, true);
        }
    }

    renderCheckboxBadges() {
        const formData = new FormData(this.form);

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

            this.createBadge(name, value, label, false);
        }
    }

    formatRangeLabel(filter, min, max) {
        if (filter === "year") {
            return `Années : ${min} → ${max}`;
        }

        if (filter === "mileage") {
            return (
                `Kilométrage : ${Number(min).toLocaleString("fr-FR")} → ` +
                `${Number(max).toLocaleString("fr-FR")} km`
            );
        }

        if (filter === "price") {
            return (
                `Prix : ${Number(min).toLocaleString("fr-FR")} € → ` +
                `${Number(max).toLocaleString("fr-FR")} €`
            );
        }

        return `${min} - ${max}`;
    }

    createBadge(filter, value, label = null, isRangeBadge = false) {
        const badge = document.createElement("span");

        const rangeFilters = ["year", "mileage", "price"];
        const isRange = rangeFilters.includes(filter);

        badge.className = isRange ? "badge badge-filter badge-filter-range" : "badge badge-filter";

        badge.dataset.filter = filter;
        badge.dataset.value = value;

        const text = document.createElement("span");
        text.textContent = label || value;

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
