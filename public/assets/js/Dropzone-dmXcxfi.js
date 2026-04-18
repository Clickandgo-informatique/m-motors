export default class Dropzone {
  constructor(element) {
    this.el = element;

    this.uploadUrl = element.dataset.uploadUrl;
    this.deleteUrlTemplate = element.dataset.deleteUrl;
    this.destination = element.dataset.destination;
    this.previewId = element.dataset.previewId;

    this.previewContainer = document.querySelector(`#${this.previewId}`);
    this.uploadBtn = document.querySelector(".dz-upload-btn");

    this.filesQueue = [];

    this.init();
  }

  init() {
    // input file caché
    this.input = document.createElement("input");
    this.input.type = "file";
    this.input.multiple = true;
    this.input.classList.add("d-none");
    this.el.appendChild(this.input);

    // clic zone => file picker
    this.el.addEventListener("click", e => {
      if (!e.target.classList.contains("dz-delete")) {
        this.input.click();
      }
    });

    // drag & drop
    this.el.addEventListener("dragover", e => e.preventDefault());
    this.el.addEventListener("drop", e => {
      e.preventDefault();
      this.addToQueue(e.dataTransfer.files);
    });

    // sélection input
    this.input.addEventListener("change", e => {
      this.addToQueue(e.target.files);
    });

    // bouton upload
    if (this.uploadBtn) {
      this.uploadBtn.addEventListener("click", () => this.uploadQueue());
    }

    // delete preview existants
    this.previewContainer.addEventListener("click", e => {
      if (e.target.classList.contains("dz-delete")) {
        this.deleteFile(e.target.dataset.id, e.target.closest(".dz-thumb"));
      }
    });

    this.updateButtonState();
  }

  addToQueue(files) {
    const arr = Array.from(files);

    arr.forEach(file => {
      this.filesQueue.push(file);

      const div = document.createElement("div");
      div.classList.add("dz-thumb", "dz-staging");

      div.innerHTML = `
                <div class="dz-file-preview dz-generic">
                    ${file.name
                      .split(".")
                      .pop()
                      .toUpperCase()}
                </div>
                <div class="dz-filename">${file.name}</div>
            `;

      this.previewContainer.appendChild(div);
    });

    this.updateButtonState();
  }

  async uploadQueue() {
    if (this.filesQueue.length === 0) return;

    const formData = new FormData();

    this.filesQueue.forEach(file => {
      formData.append("file[]", file);
    });

    formData.append("destination", this.destination);

    const response = await fetch(this.uploadUrl, {
      method: "POST",
      body: formData
    });

    const data = await response.json();

    this.renderUploaded(data.documents || data.urls || []);

    this.filesQueue = [];
    this.updateButtonState();
  }

  renderUploaded(files) {
    this.previewContainer.innerHTML = "";

    files.forEach(file => {
      const div = document.createElement("div");
      div.classList.add("dz-thumb");
      div.dataset.id = file.id;

      div.innerHTML = `
                <img src="/uploads/${file.path}" />
                <div class="dz-filename">${file.fileName}</div>
                <button class="dz-delete" data-id="${file.id}">×</button>
            `;

      this.previewContainer.appendChild(div);
    });
  }

  async deleteFile(id, element) {
    const url = this.deleteUrlTemplate.replace("__id__", id);

    await fetch(url, { method: "DELETE" });

    element.classList.add("dz-removing");

    setTimeout(() => element.remove(), 200);

    this.updateButtonState();
  }

  updateButtonState() {
    if (!this.uploadBtn) return;

    const hasFiles = this.filesQueue.length > 0;

    this.uploadBtn.disabled = !hasFiles;
    this.uploadBtn.classList.toggle("disabled", !hasFiles);
    this.uploadBtn.textContent = hasFiles
      ? `Envoyer (${this.filesQueue.length})`
      : "Envoyer les fichiers";
  }
}
