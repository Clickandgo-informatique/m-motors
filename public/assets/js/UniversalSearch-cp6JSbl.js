export default class UniversalSearch {
    constructor(element) {
        this.input = element;
        this.endpoint = element.dataset.path;
        this.targetSelector = element.dataset.target;
        this.target = document.querySelector(this.targetSelector);

        if (!this.endpoint || !this.target) {
            console.warn('UniversalSearch: configuration incomplète', element);
            return;
        }

        this.bindEvents();
    }

    bindEvents() {
        this.input.addEventListener('input', () => {
            const q = this.input.value.trim();

            if (q.length < 2) {
                this.clearResults();
                return;
            }

            this.search(q);
        });
    }

    async search(q) {
        try {
            const response = await fetch(`${this.endpoint}?q=${encodeURIComponent(q)}`);
            const data = await response.json();
            this.renderResults(data);
        } catch (e) {
            console.error('UniversalSearch error:', e);
        }
    }

    renderResults(data) {
        this.target.innerHTML = '';

        if (!data.length) {
            this.target.innerHTML = '<li>Aucun résultat</li>';
            return;
        }

        data.forEach(item => {
            const li = document.createElement('li');
            li.textContent = Object.values(item).join(' ');
            this.target.appendChild(li);
        });
    }

    clearResults() {
        this.target.innerHTML = '';
    }
}
