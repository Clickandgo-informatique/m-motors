export default class FetchForm {
    constructor(form) {
        if (!(form instanceof HTMLFormElement)) return;

        this.form = form;
        this._timeout = null;

        this.init();
    }

    init() {
        this.form.addEventListener("submit", e => {
            e.preventDefault();
            this.send();
        });

        this.form.addEventListener("change", e => {
            if (!this.form.contains(e.target)) return;
            this.scheduleSend();
        });

        this.form.addEventListener("input", e => {
            if (!this.form.contains(e.target)) return;
            this.scheduleSend();
        });

        this.initResetButton();
    }

    initResetButton() {
        const btn = this.form.querySelector("[data-reset-filters]");
        if (!btn) return;

        btn.addEventListener("click", e => {
            e.preventDefault();

            this.resetFilters();

            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    this.send();
                });
            });
        });
    }

    resetFilters() {
        this.form.reset();

        const page = this.form.querySelector('[name="page"]');
        if (page) page.value = 1;

        this.form.querySelectorAll('input[type="hidden"]').forEach(input => {
            if (input.name !== "page") input.value = "";
        });

        this.form.querySelectorAll(".double-slider").forEach(slider => {
            const min = Number(slider.dataset.min);
            const max = Number(slider.dataset.max);

            const map = {
                year: "registrationYear",
                mileage: "mileage",
                price: "price"
            };

            const base = map[slider.dataset.filter] || slider.dataset.filter;

            const inputMin = this.form.querySelector(`input[name="filters[${base}Min]"]`);

            const inputMax = this.form.querySelector(`input[name="filters[${base}Max}"]`);

            if (inputMin) inputMin.value = min;
            if (inputMax) inputMax.value = max;

            slider.dispatchEvent(new CustomEvent("sliderReset", { bubbles: true }));
        });
    }

    scheduleSend() {
        clearTimeout(this._timeout);

        this._timeout = setTimeout(() => {
            this.send();
        }, 120);
    }

    async send() {
        const url = this.form.dataset.fetchUrl;
        const target = document.querySelector(this.form.dataset.target);

        if (!url || !target) return;

        const formData = new FormData(this.form);
        const params = new URLSearchParams();

        formData.forEach((value, key) => {
            if (value !== null && value !== "") {
                params.append(key, value);
            }
        });

        try {
            const res = await fetch(`${url}?${params.toString()}`);
            const data = await res.json();

            this.render(target, data);
        } catch (e) {
            console.error(e);
        }
    }

    render(target, data) {
        target.innerHTML = data.results || "";

        this.renderPagination(data);
        this.renderFiltersSummary(data);

        this.updateBadges();
    }

    renderPagination(data) {
        const top = this.form.dataset.paginationTop
            ? document.querySelector(this.form.dataset.paginationTop)
            : null;

        const bottom = this.form.dataset.paginationBottom
            ? document.querySelector(this.form.dataset.paginationBottom)
            : null;

        if (data.paginationTop && top) top.innerHTML = data.paginationTop;
        if (data.paginationBottom && bottom) bottom.innerHTML = data.paginationBottom;
        if (data.pagination) {
            if (top) top.innerHTML = data.pagination;
            if (bottom) bottom.innerHTML = data.pagination;
        }
    }

    renderFiltersSummary(data) {
        const target = document.querySelector("#filters-summary");
        if (target && data.filtersSummary) {
            target.innerHTML = data.filtersSummary;
        }
    }

    updateBadges() {
        const container = document.querySelector("#filters-summary");
        const form = this.form;

        if (!container || !form) return;

        container.innerHTML = "";

        const formData = new FormData(form);

        const ranges = {
            mileage: ["filters[mileageMin]", "filters[mileageMax]"],
            year: ["filters[registrationYearMin]", "filters[registrationYearMax]"],
            price: ["filters[priceMin]", "filters[priceMax]"]
        };

        for (const [filter, [minKey, maxKey]] of Object.entries(ranges)) {
            const min = formData.get(minKey);
            const max = formData.get(maxKey);

            if (min !== "" && max !== "") {
                container.appendChild(this.createBadge(filter, `${min}-${max}`));
            }
        }

        for (const [key, value] of formData.entries()) {
            const match = key.match(/^filters\[(.+?)\](\[\])?$/);
            if (!match) continue;

            const name = match[1];
            const isArray = !!match[2];

            if (!isArray || !value) continue;

            container.appendChild(this.createBadge(name, value));
        }
    }

    createBadge(filter, value) {
        const badge = document.createElement("span");

        const isRange = ["year", "mileage", "price"].includes(filter);

        badge.className = isRange ? "badge-filter badge-filter-range" : "badge-filter";

        badge.dataset.filter = filter;
        badge.dataset.value = value;

        const span = document.createElement("span");
        span.textContent = value;

        badge.appendChild(span);

        if (!isRange) {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "badge-remove";
            btn.textContent = "×";
            badge.appendChild(btn);
        }

        return badge;
    }
}
