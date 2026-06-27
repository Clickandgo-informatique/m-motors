export default class FetchForm {
    constructor(form) {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        this.form = form;
        this.isLoading = false;
        this.abortController = null;
        this._timeout = null;
        this.lastTrigger = "submit";
        this.init();
    }

    init() {
        // submit manuel
        this.form.addEventListener("submit", e => {
            e.preventDefault();
            this.lastTrigger = "submit";
            this.send();
        });

        // filtres automatiques
        this.form.addEventListener("change", e => {
            if (!this.form.contains(e.target)) return;

            this.lastTrigger = "filter";

            // Remettre la pagination à 1 à chaque changement de filtre
            let pageInput = this.form.querySelector("input[name='page']");
            if (!pageInput) {
                pageInput = document.createElement("input");
                pageInput.type = "hidden";
                pageInput.name = "page";
                this.form.appendChild(pageInput);
            }
            pageInput.value = 1;

            this.scheduleSend();
        });

        this.form.addEventListener("input", e => {
            if (!this.form.contains(e.target)) return;

            this.lastTrigger = "filter";

            //Remettre la pagination à 1 à chaque modification de slider
            let pageInput = this.form.querySelector("input[name='page']");
            if (!pageInput) {
                pageInput = document.createElement("input");
                pageInput.type = "hidden";
                pageInput.name = "page";
                this.form.appendChild(pageInput);
            }
            pageInput.value = 1;

            this.scheduleSend();
        });

        // pagination (AJAX navigation)
        this.form.addEventListener("click", e => {
            const link = e.target.closest("[data-page]");
            if (!link) return;

            e.preventDefault();

            const page = link.dataset.page;

            this.goToPage(page);
        });

        this.initResetButton();
    }

    initResetButton() {
        const resetBtn = this.form.querySelector("[data-reset-filters]");

        if (!resetBtn) return;

        resetBtn.addEventListener("click", e => {
            e.preventDefault();

            this.resetFilters();

            this.lastTrigger = "reset";

            this.send();
        });
    }

    resetFilters() {
        this.form.reset();

        // Remettre la page à 1
        const pageInput = this.form.querySelector('[name="page"]');
        if (pageInput) {
            pageInput.value = 1;
        }

        // Remettre les sliders à leurs valeurs par défaut
        this.form.querySelectorAll(".double-slider").forEach(slider => {
            const filter = slider.dataset.filter;

            const defaultMin = slider.dataset.min;
            const defaultMax = slider.dataset.max;

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

            // notifier le slider pour qu'il se repositionne
            slider.dispatchEvent(
                new CustomEvent("sliderChanged", {
                    bubbles: true,
                    detail: { filter, min: defaultMin, max: defaultMax }
                })
            );
        });
    }

    goToPage(page) {
        let pageInput = this.form.querySelector('[name="page"]');

        if (!pageInput) {
            pageInput = document.createElement("input");
            pageInput.type = "hidden";
            pageInput.name = "page";
            this.form.appendChild(pageInput);
        }

        pageInput.value = page;

        this.lastTrigger = "pagination";

        this.send();
    }

    scheduleSend() {
        clearTimeout(this._timeout);

        this._timeout = setTimeout(() => {
            this.send();
        }, 120);
    }

    async send() {
        if (this.abortController) {
            this.abortController.abort();
        }

        this.abortController = new AbortController();

        this.isLoading = true;
        this.form.dataset.loading = "1";

        const url = this.form.dataset.fetchUrl;
        const target = document.querySelector(this.form.dataset.target);

        if (!url || !target) {
            this.cleanup();
            return;
        }

        if (this.lastTrigger === "reset") {
            const pageInput = this.form.querySelector('[name="page"]');

            if (pageInput) {
                pageInput.value = 1;
            }
        }

        try {
            const formData = new FormData(this.form);
            const params = new URLSearchParams();

            formData.forEach((value, key) => {
                if (value !== null && value !== "") {
                    params.append(key, value);
                }
            });

            const response = await fetch(`${url}?${params.toString()}`, {
                method: "GET",
                signal: this.abortController.signal
            });

            const data = await response.json();

            this.renderTarget(target, data);
            this.renderPagination(data);
            this.renderFiltersSummary(data);

            window.dispatchEvent(new Event("ui:updated"));

            if (window.__filterBadges?.updateBadges) {
                window.__filterBadges.updateBadges();
            }
        } catch (e) {
            if (e.name !== "AbortError") {
                console.error("[FetchForm]", e);
            }
        } finally {
            this.cleanup();
        }
    }

    renderTarget(target, data) {
        if (data.results) {
            target.innerHTML = data.results;
            return;
        }

        if (data.list) {
            target.innerHTML = data.list;
        }
    }

    renderPagination(data) {
        const topSelector = this.form.dataset.paginationTop;
        const bottomSelector = this.form.dataset.paginationBottom;

        const top = topSelector ? document.querySelector(topSelector) : null;
        const bottom = bottomSelector ? document.querySelector(bottomSelector) : null;

        if (data.paginationTop && top) {
            top.innerHTML = data.paginationTop;
        }

        if (data.paginationBottom && bottom) {
            bottom.innerHTML = data.paginationBottom;
        }

        if (data.pagination) {
            if (top) top.innerHTML = data.pagination;
            if (bottom) bottom.innerHTML = data.pagination;
        }
    }

    renderFiltersSummary(data) {
        if (!data.filtersSummary) return;

        const target = document.querySelector(
            this.form.dataset.filtersTarget || "#filters-summary"
        );

        if (target) {
            target.innerHTML = data.filtersSummary;
        }
    }

    cleanup() {
        this.isLoading = false;
        delete this.form.dataset.loading;
    }
}
