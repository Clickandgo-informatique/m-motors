export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.isLoading = false;
    this.timer = null;
    this.ready = true;

    this.init();
  }

  init() {
    this.form.addEventListener("input", e => this.onChange(e));
    this.form.addEventListener("change", e => this.onChange(e));

    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });
  }

  onChange(e) {
    console.log("[FetchForm] onChange triggered", e.target);

    const el = e.target;

    if (!(el instanceof HTMLElement)) return;

    if (el.closest("[data-module='autocomplete']")) {
      console.log("[FetchForm] ignored autocomplete event");
      return;
    }

    console.log("[FetchForm] scheduling debounce");
    this.debounce();
  }

  debounce() {
    console.log("[FetchForm] debounce called");

    clearTimeout(this.timer);

    this.timer = setTimeout(() => {
      console.log("[FetchForm] debounce executed → send()");
      this.send();
    }, 200);
  }

  send() {
    console.log("[FetchForm] send() START");

    if (this.isLoading) {
      console.log("[FetchForm] blocked (loading)");
      return;
    }

    const url = this.form.dataset.fetchUrl;
    const target = document.querySelector(this.form.dataset.target);

    console.log("[FetchForm] url:", url);
    console.log("[FetchForm] target selector:", this.form.dataset.target);
    console.log("[FetchForm] target element:", target);

    if (!url || !target) {
      console.warn("[FetchForm] missing url or target");
      return;
    }

    this.isLoading = true;

    const formData = new FormData(this.form);

    console.log("[FetchForm] FORM DATA RAW:");
    console.log([...formData.entries()]);

    const params = new URLSearchParams();

    const seen = new Set();

    for (const [k, v] of formData.entries()) {
      console.log("[FetchForm] param:", k, v);

      if (seen.has(k)) {
        console.warn("[FetchForm] DUPLICATE PARAM:", k);
        continue;
      }

      seen.add(k);
      params.append(k, v);
    }

    console.log("[FetchForm] FINAL QUERY:", params.toString());
    console.log("[FetchForm] FORM ELEMENT:", this.form);
    console.log(
      "[FetchForm] hidden field:",
      this.form.querySelector("[data-autocomplete-value]")
    );

    const fullUrl = url + "?" + params.toString();

    console.log("[FetchForm] FETCH URL:", fullUrl);

    fetch(fullUrl, {
      method: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    })
      .then(res => {
        console.log("[FetchForm] response received");
        return res.text();
      })
      .then(html => {
        console.log("[FetchForm] injecting HTML");

        target.innerHTML = html;

        window.dispatchEvent(new Event("ui:updated"));
      })
      .catch(err => {
        console.error("[FetchForm] ERROR:", err);
      })
      .finally(() => {
        console.log("[FetchForm] DONE");
        this.isLoading = false;
      });
  }
}
