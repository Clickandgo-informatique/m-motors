export default class Dropzone {
  constructor(element) {
    this.el = element;

    this.uploadUrl = element.dataset.uploadUrl;
    this.deleteUrlTemplate = element.dataset.deleteUrl;
    this.documentsUrl = element.dataset.documentsUrl;

    this.destination = element.dataset.destination;
    this.status = element.dataset.status;

    this.previewContainer = this.el.querySelector(
      `#${element.dataset.previewId}`
    );

    this.uploadBtn = this.el.querySelector(".dz-upload-btn");

    this.filesQueue = [];
    this.fileKeys = new Set();

    this.locked = element.dataset.locked === "1";

    this.init();
    this.refreshState();
  }

  init() {
    this.createInput();

    if (this.locked || !this.canUpload()) {
      this.disable();
      return;
    }

    this.bindEvents();
  }

  createInput() {
    this.input = document.createElement("input");
    this.input.type = "file";
    this.input.multiple = true;
    this.input.classList.add("d-none");

    this.el.appendChild(this.input);
  }

  bindEvents() {
    this.el.addEventListener("click", e => {
      if (!e.target.classList.contains("dz-delete")) {
        this.input.click();
      }
    });

    this.el.addEventListener("dragover", e => e.preventDefault());

    this.el.addEventListener("drop", e => {
      e.preventDefault();
      this.addToQueue(e.dataTransfer.files);
    });

    this.input.addEventListener("change", e => {
      this.addToQueue(e.target.files);
      this.input.value = "";
    });

    if (this.uploadBtn) {
      this.uploadBtn.addEventListener("click", () => this.uploadQueue());
    }
  }

  canUpload() {
    const allowed = [
      "draft",
      "vehicle_selected",
      "documents_pending",
      "documents_review"
    ];

    return allowed.includes(this.status);
  }

  disable() {
    this.input.disabled = true;

    if (this.uploadBtn) {
      this.uploadBtn.disabled = true;
      this.uploadBtn.textContent = "Upload verrouillé";
    }

    this.el.classList.add("disabled");
  }

  async refreshState() {
    if (!this.documentsUrl || !this.previewContainer) return;

    const res = await fetch(this.documentsUrl);
    const data = await res.json();

    this.renderDocuments(data.documents || []);
  }

  renderDocuments(files) {
    if (!Array.isArray(files) || !this.previewContainer) return;

    this.previewContainer.innerHTML = "";

    files.forEach(file => {
      this.previewContainer.appendChild(this.createServerThumb(file));
    });
  }

  createServerThumb(file) {
    const div = document.createElement("div");
    div.classList.add("dz-thumb");
    div.dataset.id = file.id;

    const ext = file.fileName
      ?.split(".")
      .pop()
      ?.toLowerCase();

    div.innerHTML = `
      <div class="dz-preview">
        ${this.getPreviewHtml(ext, file)}
      </div>
      <div class="dz-filename">
        ${file.originalName ?? file.fileName}
      </div>
      <div class="dz-meta">
        ${file.createdAt ?? ""}
      </div>
    `;

    const btn = document.createElement("button");
    btn.classList.add("dz-delete");
    btn.textContent = "×";

    btn.addEventListener("click", async e => {
      e.stopPropagation();

      if (!confirm("Supprimer ce fichier ?")) return;

      const url = this.deleteUrlTemplate.replace("__id__", file.id);

      await fetch(url, { method: "DELETE" });

      this.refreshState();
    });

    div.appendChild(btn);

    return div;
  }

  addToQueue(files) {
    Array.from(files).forEach(file => {
      const key = `${file.name}_${file.size}`;

      if (this.fileKeys.has(key)) return;

      this.fileKeys.add(key);
      this.filesQueue.push(file);

      this.previewContainer?.appendChild(this.createLocalThumb(file));
    });

    this.updateButtonState();
  }

  createLocalThumb(file) {
    const div = document.createElement("div");
    div.classList.add("dz-thumb", "dz-staging");

    const ext = file.name
      .split(".")
      .pop()
      .toUpperCase();

    div.innerHTML = `
      <div class="dz-file-preview">${ext}</div>
      <div class="dz-filename">${file.name}</div>
    `;

    return div;
  }

  async uploadQueue() {
    if (!this.filesQueue.length) return;

    const formData = new FormData();

    this.filesQueue.forEach(file => {
      formData.append("file[]", file);
    });

    formData.append("destination", this.destination);

    await fetch(this.uploadUrl, {
      method: "POST",
      body: formData
    });

    this.filesQueue = [];
    this.fileKeys.clear();

    await this.refreshState();
    this.updateButtonState();
  }

  updateButtonState() {
    if (!this.uploadBtn) return;

    const hasFiles = this.filesQueue.length > 0;

    this.uploadBtn.disabled = !hasFiles;
    this.uploadBtn.textContent = hasFiles
      ? `Envoyer (${this.filesQueue.length})`
      : "Envoyer les fichiers";
  }

  getPreviewHtml(ext, file) {
    if (["jpg", "jpeg", "png", "webp"].includes(ext)) {
      return `<img src="/uploads/${file.path}">`;
    }

    if (ext === "pdf") return "PDF";
    if (["doc", "docx"].includes(ext)) return "WORD";
    if (["xls", "xlsx"].includes(ext)) return "EXCEL";

    return "Fichier";
  }

  canUploadFile() {
    const map = {
      draft: ["UPLOAD"],
      vehicle_selected: ["IDENTITY"],
      documents_pending: ["IDENTITY", "CONTRACT"],
      documents_review: ["CONTRACT"]
    };

    return map[this.status] || ["UPLOAD"];
  }
}
