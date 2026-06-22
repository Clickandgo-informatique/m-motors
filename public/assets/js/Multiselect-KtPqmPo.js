console.log("classe multiselect initialisée");
export default class Multiselect {
  constructor(wrapper) {
    this.wrapper = wrapper;
    this.url = wrapper.dataset.url;

    this.selectedItems = [];
    this.availableItems = [];
  }

  // création de la structure HTML du multiselect
  createMultiselect() {
    // conteneur des éléments sélectionnés
    this.selectedItemsDiv = document.createElement("div");
    this.selectedItemsDiv.classList.add("multiselect-selected");

    // conteneur des éléments disponibles
    this.availableItemsDiv = document.createElement("div");
    this.availableItemsDiv.classList.add("multiselect-available");

    this.wrapper.appendChild(this.selectedItemsDiv);
    this.wrapper.appendChild(this.availableItemsDiv);
  }

  // récupération des données
  async fetchData() {
    const response = await fetch(this.url);

    if (!response.ok) {
      throw new Error("Impossible de récupérer les données du multiselect.");
    }

    this.availableItems = await response.json();

    console.log(this.availableItems);
  }

  async fillAvalaibleItems() {
    //Création de la liste des items disponibles
    this.availableItemsDiv.innerHTML = "";

    const ul = document.createElement("ul");
    ul.classList.add("list-wrapper");

    for (const item of this.availableItems) {
      const li = document.createElement("li");
      li.dataset.id = item.id;
      li.textContent = item.label;

      ul.appendChild(li);
    }

    this.availableItemsDiv.appendChild(ul);
  }

  // initialisation du composant
  async initMultiselect() {
    this.createMultiselect();
    await this.fetchData();
    await this.fillAvalaibleItems();
  }
}
