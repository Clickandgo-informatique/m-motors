import VehicleFilterStore from "./VehicleFilterStore.js";
import VehiclesFilters from "./VehiclesFilters.js";

export default class VehicleFilterApp {
  constructor(form) {
    this.store = new VehicleFilterStore();
    this.form = form;

    this.ui = new VehiclesFilters(form, this.store);

    this.bindStore();
  }

  bindStore() {
    this.store.subscribe(state => {
      this.fetch(state);
    });
  }

  async fetch(state) {
    const res = await fetch("/vehicles/ajax/list", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      },
      body: JSON.stringify(state)
    });

    const data = await res.json();

    this.render(data);
  }

  render(data) {
    document.querySelector("#vehicles-results").innerHTML = data.list;

    const summary = document.querySelector('[data-target="filters-summary"]');
    if (summary) {
      summary.innerHTML = data.filtersSummary;
    }

    document.querySelector('[data-target="pagination-top"]').innerHTML =
      data.pagination_top;

    document.querySelector('[data-target="pagination-bottom"]').innerHTML =
      data.pagination_bottom;
  }
}
