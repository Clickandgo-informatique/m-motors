export default class Dropzone {
  constructor(element) {
    this.el = element;

    // URLs backend
    this.uploadUrl = element.dataset.uploadUrl;
    this.deleteUrl = element.dataset.deleteUrl;

    // Configuration
    this.destination = element.dataset.destination;
    this.previewId = element.dataset.previewId;

    // DOM
    this.previewContainer = document.querySelector(`#${this.previewId}`);
    this.hiddenField = document.querySelector("#images-field");

    // State
    this.queue = [];

    this.init();
  }

  /**
   * Initialisation du composant
   */
  init() {
    // Input file caché (créé dynamiquement)
    this.input = document.createElement("input");
    this.input.type = "file";
    this.input.multiple = true;
    this.input.name = "file[]";
    this.input.classList.add("d-none");

    this.el.appendChild(this.input);

    // Click sur zone => ouverture file picker
    this.el.addEventListener("click", e => {
      if (!e.target.classList.contains("dz-delete")) {
        this.input.click();
      }
    });

    // Drag & drop
    this.el.addEventListener("dragover", e => {
      e.preventDefault();
      this.el.classList.add("dz-uploading");
    });

    this.el.addEventListener("dragleave", () => {
      this.el.classList.remove("dz-uploading");
    });

    this.el.addEventListener("drop", e => {
      e.preventDefault();
      this.el.classList.remove("dz-uploading");
      this.handleFiles(e.dataTransfer.files);
    });

    // Sélection fichier classique
    this.input.addEventListener("change", () => {
      this.handleFiles(this.input.files);
      this.input.value = "";
    });

    // Suppression (event delegation)
    this.previewContainer.addEventListener("click", e => {
      if (e.target.classList.contains("dz-delete")) {
        this.deleteFile(e.target.dataset.id, e.target.closest(".dz-thumb"));
      }
    });

    this.updateCounter();
  }

  /**
   * Point d'entrée fichiers (upload + staging)
   */
  handleFiles(files) {
    this.queue = [...files];

    this.renderStaging(files);
    this.uploadQueue();
  }

  /**
   * Preview instantané côté client avant upload
   */
  renderStaging(files) {
    files.forEach(file => {
      const reader = new FileReader();

      reader.onload = e => {
        const div = document.createElement("div");
        div.classList.add("dz-thumb", "dz-staging");

        div.innerHTML = `
                    <img src="${e.target.result}" width="120" class="rounded border">
                    <span class="badge bg-secondary">Upload...</span>
                `;

        this.previewContainer.appendChild(div);
      };

      reader.readAsDataURL(file);
    });
  }

  /**
   * Upload réel vers backend
   */
  async uploadQueue() {
    const formData = new FormData();

    this.queue.forEach(file => {
      formData.append("file[]", file);
    });

    const response = await fetch(this.uploadUrl, {
      method: "POST",
      body: formData
    });

    const data = await response.json();

    // suppression previews staging
    this.previewContainer
      .querySelectorAll(".dz-staging")
      .forEach(el => el.remove());

    // affichage réel
    this.renderPreview(data.documents);
    this.syncHiddenField(data.documents);
    this.updateCounter();

    this.queue = [];
  }

  /**
   * Affichage fichiers persistés
   */
  renderPreview(documents) {
    documents.forEach(doc => {
      const div = document.createElement("div");
      div.classList.add("dz-thumb");

      div.dataset.id = doc.id;

      div.innerHTML = `
                <img src="/uploads/${doc.path}" width="120" class="rounded border">
                <button class="dz-delete" data-id="${doc.id}">×</button>
            `;

      this.previewContainer.appendChild(div);
    });
  }

  /**
   * Synchronisation champ caché JSON
   */
  syncHiddenField(documents) {
    if (!this.hiddenField) return;

    const current = this.hiddenField.value
      ? JSON.parse(this.hiddenField.value)
      : [];

    const mapped = documents.map(d => ({
      id: d.id,
      fileName: d.fileName,
      path: d.path
    }));

    this.hiddenField.value = JSON.stringify([...current, ...mapped]);
  }

  /**
   * Suppression fichier
   */
  async deleteFile(documentId, element) {
    element.classList.add("dz-removing");

    const response = await fetch(this.deleteUrl.replace("__id__", documentId), {
      method: "DELETE"
    });

    if (!response.ok) return;

    setTimeout(() => {
      element.remove();
      this.removeFromHiddenField(documentId);
      this.updateCounter();
    }, 150);
  }

  /**
   * Mise à jour champ hidden après suppression
   */
  removeFromHiddenField(id) {
    if (!this.hiddenField) return;

    const current = JSON.parse(this.hiddenField.value || "[]");

    this.hiddenField.value = JSON.stringify(current.filter(d => d.id != id));
  }

  /**
   * Compteur UI
   */
  updateCounter() {
    const counter = this.el.parentElement.querySelector(".dz-counter");

    if (!counter) return;

    const count = this.previewContainer.querySelectorAll(".dz-thumb").length;

    counter.textContent =
      count === 0 ? "0 fichier" : `${count} fichier${count > 1 ? "s" : ""}`;
  }
}
