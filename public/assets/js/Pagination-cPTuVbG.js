export default class Pagination {
  constructor() {
    this.init();
  }

  init() {
    document.addEventListener("click", e => {
      const link = e.target.closest("a[data-pagination-link]");
      if (!link) return;

      e.preventDefault();

      this.handleClick(link);
    });
  }

  async handleClick(link) {
    const url = link.getAttribute("href");

    const form = document.querySelector("form[data-fetch-form]");
    if (!form) {
      window.location.href = url;
      return;
    }

    const targetAttr = form.getAttribute("data-target") || "";
    const targetSelectors = targetAttr
      .split(",")
      .map(s => s.trim())
      .filter(Boolean);

    try {
      const response = await fetch(url, {
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      const html = await response.text();

      targetSelectors.forEach(selector => {
        const targets = document.querySelectorAll(selector);

        targets.forEach(el => {
          el.innerHTML = html;
        });
      });
    } catch (e) {
      console.error("Pagination error:", e);
    }
  }
}
