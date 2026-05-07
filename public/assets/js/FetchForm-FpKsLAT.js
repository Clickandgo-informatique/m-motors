export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.isLoading = false;
    this.ready = false;

    this.init();
  }

  init() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    setTimeout(() => {
      this.ready = true;
    }, 200);
  }

  send() {
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl;
    const target = document.querySelector(this.form.dataset.target);

    if (!url || !target) {
      console.warn("[FetchForm] missing url or target");
      return;
    }

    this.isLoading = true;

    const fd = new FormData(this.form);

    console.log("[FetchForm] form data:", [...fd.entries()]);

    const vehicleId = fd.get("vehicleId") || fd.get("q");

    const params = new URLSearchParams();

    if (vehicleId) {
      params.set("vehicleId", vehicleId);
    }

    const fullUrl = `${url}?${params.toString()}`;

    console.log("[FetchForm] request:", fullUrl);

    fetch(fullUrl, {
      method: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    })
      .then(r => r.text())
      .then(html => {
        target.innerHTML = html;

        window.dispatchEvent(new Event("ui:updated"));
      })
      .finally(() => {
        this.isLoading = false;
      });
  }
}
