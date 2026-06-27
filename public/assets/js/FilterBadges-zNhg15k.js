export default class FilterBadges {
    constructor(container, form, onRemoveCallback) {
        this.container = container;
        this.form = form;
        this.onRemoveCallback = onRemoveCallback;

        this._updateScheduled = null;

        if (!this.container || !this.form) return;

        this.bindEvents();
    }

    updateBadges() {
        clearTimeout(this._updateScheduled);

        this._updateScheduled = setTimeout(() => {
            this._renderBadges();
        }, 50);
    }

    _renderBadges() {
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

            if (min !== null && min !== "" && max !== null && max !== "") {
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

        const isRangeBadge = ["year", "mileage", "price"].includes(filter);

        badge.className = isRangeBadge
            ? "badge badge-filter badge-filter-range"
            : "badge badge-filter";

        badge.dataset.filter = filter;
        badge.dataset.value = value;

        const text = document.createElement("span");
        text.textContent = label || value;

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
