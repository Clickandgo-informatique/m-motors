export default class FetchForm {
  constructor() {
    this.forms = document.querySelectorAll("form[data-fetch-form]");
    this.init();
  }

  init() {
    this.forms.forEach(form => {
      form.addEventListener("submit", e => this.handleSubmit(e, form));
    });
  }

  async handleSubmit(event, form) {
    event.preventDefault();

    const url = form.getAttribute("action");
    const method = (form.getAttribute("method") || "GET").toUpperCase();

    const targetAttr = form.getAttribute("data-target") || "";
    const targetSelectors = targetAttr
      .split(",")
      .map(s => s.trim())
      .filter(Boolean);

    const submitButton = form.querySelector('[type="submit"]');
    if (submitButton) submitButton.disabled = true;

    try {
      const formData = new FormData(form);

      const fetchOptions = {
        method,
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      };

      let response;

      if (method === "GET") {
        const params = new URLSearchParams(formData);
        response = await fetch(`${url}?${params.toString()}`, fetchOptions);
      } else {
        fetchOptions.body = formData;
        response = await fetch(url, fetchOptions);
      }

      return await this.handleResponse(response, targetSelectors);
    } catch (error) {
      console.error("FetchForm error:", error);
    } finally {
      if (submitButton) submitButton.disabled = false;
    }
  }

  async handleResponse(response, targetSelectors) {
    const html = await response.text();

    if (!targetSelectors.length) {
      document.body.innerHTML = html;
      return;
    }

    let updated = false;

    targetSelectors.forEach(selector => {
      const targets = document.querySelectorAll(selector);

      if (targets.length) {
        targets.forEach(el => {
          el.innerHTML = html;
        });
        updated = true;
      } else {
        console.warn(`FetchForm: target not found ${selector}`);
      }
    });

    if (!updated) {
      console.warn("FetchForm: no valid targets updated");
    }
  }
}
