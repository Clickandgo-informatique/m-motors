export default class Dropzone {
  constructor(element) {
    this.el = element;

    // =========================================================
    // CONFIGURATION BACKEND
    // =========================================================
    this.uploadUrl = element.dataset.uploadUrl;
    this.deleteUrlTemplate = element.dataset.deleteUrl;
    this.documentsUrl = element.dataset.documentsUrl;

    // dossier cible métier (ex: dossier id ou type)
    this.destination = element.dataset.destination;

    // statut workflow du dossier (important pour lock UI)
    this.status = element.dataset.status;

    // =========================================================
    // UI
    // =========================================================
    this.previewContainer = document.querySelector(
      `#${element.dataset.previewId}`
    );

    this.uploadBtn = document.querySelector(".dz-upload-btn");

    // =========================================================
    // STATE LOCAL
    // =========================================================
    this.filesQueue = [];
    this.fileKeys = new Set();

    this.locked = element.dataset.locked === "1";

    this.init();
    this.refreshState();
  }

  // =========================================================
  // INITIALISATION
  // =========================================================
  init() {
    console.log("Dropzone.js initialisé");
    this.createInput();

    if (this.locked || !this.canUpload()) {
      this.disable();
      return;
    }

    this.bindEvents();
  }

  // =========================================================
  // INPUT FILE HIDDEN
  // =========================================================
  createInput() {
    this.input = document.createElement("input");
    this.input.type = "file";
    this.input.multiple = true;
    this.input.classList.add("d-none");

    this.el.appendChild(this.input);
  }

  // =========================================================
  // EVENTS
  // =========================================================
  bindEvents() {
    // click zone
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

    // file selection
    this.input.addEventListener("change", e => {
      this.addToQueue(e.target.files);
      this.input.value = "";
    });

    // upload button
    if (this.uploadBtn) {
      this.uploadBtn.addEventListener("click", () => this.uploadQueue());
    }
  }

  // =========================================================
  // RULES METIER (WORKFLOW AWARE)
  // =========================================================
  canUpload() {
    const allowedStatuses = [
      "draft",
      "vehicle_selected",
      "documents_pending",
      "documents_review"
    ];

    return allowedStatuses.includes(this.status);
  }

  // =========================================================
  // LOCK UI
  // =========================================================
  disable() {
    this.input.disabled = true;

    if (this.uploadBtn) {
      this.uploadBtn.disabled = true;
      this.uploadBtn.textContent = "Upload verrouillé";
    }

    this.el.classList.add("disabled");
  }

  // =========================================================
  // CHARGEMENT INITIAL SERVEUR
  // =========================================================
  async refreshState() {
    if (!this.documentsUrl) return;

    const res = await fetch(this.documentsUrl);
    const data = await res.json();

    this.renderDocuments(data.documents || []);
  }

  // =========================================================
  // RENDER DOCUMENTS SERVEUR
  // =========================================================
  renderDocuments(files) {
    if (!Array.isArray(files)) {
      console.error("Dropzone: invalid data format", files);
      return;
    }

    this.previewContainer.innerHTML = "";

    files.forEach(file => {
      const div = this.createServerThumb(file);
      this.previewContainer.appendChild(div);
    });
  }

  // =========================================================
  // THUMB SERVER
  // =========================================================
  createServerThumb(file) {
    const div = document.createElement("div");
    div.classList.add("dz-thumb");
    div.dataset.id = file.id;

    const ext = file.fileName
      .split(".")
      .pop()
      .toLowerCase();

    let preview = this.getPreviewHtml(ext, file);

    div.innerHTML = `
      <div class="dz-preview">${preview}</div>

      <div class="dz-filename">
        ${file.originalName ?? file.fileName}
      </div>

      <div class="dz-meta">
        Envoyé le ${file.createdAt ?? ""}
      </div>
    `;

    // delete button
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

  // =========================================================
  // QUEUE LOCAL (PREVIEW AVANT UPLOAD)
  // =========================================================
  addToQueue(files) {
    Array.from(files).forEach(file => {
      const key = `${file.name}_${file.size}`;

      if (this.fileKeys.has(key)) return;

      this.fileKeys.add(key);
      this.filesQueue.push(file);

      const div = this.createLocalThumb(file);
      this.previewContainer.appendChild(div);
    });

    this.updateButtonState();
  }

  // =========================================================
  // THUMB LOCAL (STAGING)
  // =========================================================
  createLocalThumb(file) {
    const div = document.createElement("div");
    div.classList.add("dz-thumb", "dz-staging");

    const ext = file.name
      .split(".")
      .pop()
      .toUpperCase();

    div.innerHTML = `
      <div class="dz-file-preview dz-generic">
        ${ext}
      </div>

      <div class="dz-filename">
        ${file.name}
      </div>
    `;

    return div;
  }

  // =========================================================
  // UPLOAD
  // =========================================================
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

  // =========================================================
  // UI BUTTON STATE
  // =========================================================
  updateButtonState() {
    if (!this.uploadBtn) return;

    const hasFiles = this.filesQueue.length > 0;

    this.uploadBtn.disabled = !hasFiles;

    this.uploadBtn.textContent = hasFiles
      ? `Envoyer (${this.filesQueue.length})`
      : "Envoyer les fichiers";
  }

  // =========================================================
  // PREVIEW HELPER
  // =========================================================
  getPreviewHtml(ext, file) {
    if (["jpg", "jpeg", "png", "webp"].includes(ext)) {
      return `<img src="/uploads/${file.path}">`;
    }

    if (ext === "pdf") {
      return `<div class="dz-file-preview dz-pdf">PDF</div>`;
    }

    if (["doc", "docx"].includes(ext)) {
      return `<div class="dz-file-preview dz-word">WORD</div>`;
    }

    if (["xls", "xlsx"].includes(ext)) {
      return `<div class="dz-file-preview dz-excel">EXCEL</div>`;
    }

    return `<div class="dz-file-preview dz-generic">Fichier</div>`;
  }
  getAllowedDocumentTypes() {
    const map = {
      draft: ["UPLOAD"],
      vehicle_selected: ["IDENTITY"],
      documents_pending: ["IDENTITY", "CONTRACT"],
      documents_review: ["CONTRACT"]
    };

    return map[this.status] || ["UPLOAD"];
  }
  canUploadFile(file) {
    return this.getAllowedDocumentTypes().length > 0;
  }
}
