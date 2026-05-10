export default class Dropzone {
  constructor(element) {
    this.el = element;

    this.uploadUrl = element.dataset.uploadUrl;
    this.deleteUrlTemplate = element.dataset.deleteUrl;
    this.documentsUrl = element.dataset.documentsUrl;
    this.destination = element.dataset.destination || null;

    this.previewContainer = this.el.querySelector(
      `#${element.dataset.previewId}`
    );

    this.uploadBtn = this.el.querySelector(".dz-upload-btn");

    this.filesQueue = [];
    this.fileKeys = new Set();

    // MODE EXPLICITE
    this.mode = this.el.dataset.mode || "media";

    this.input = null;

    this.init();
  }

  init() {
    this.createInput();
    this.bindEvents();
    this.refreshState?.();

    // uniquement si workflow
    if (this.mode === "workflow") {
      this.applyWorkflowRules();
    } else {
      this.enable(); // mode media toujours actif
    }
  }

  createInput() {
createInput() {
  this.input = document.createElement("input");

  this.input.type = "file";
  this.input.multiple = true;
  this.input.classList.add("d-none");

  this.el.appendChild(this.input);
}
  }

  bindEvents() {
    // ouverture file picker
    this.el.addEventListener("click", e => {
      console.log("dropzone cliquée");
      if (
        e.target.closest(".dz-upload-btn") ||
        e.target.closest(".dz-delete")
      ) {
        return;
      }

      if (this.input) {
        this.input.click();
      }
    });

    // drag & drop
    this.el.addEventListener("dragover", e => e.preventDefault());

    this.el.addEventListener("drop", e => {
      e.preventDefault();
      this.addToQueue(e.dataTransfer.files);
    });

    // file input change
    if (this.input) {
      this.input.addEventListener("change", e => {
        this.addToQueue(e.target.files);
        this.input.value = "";
      });
    }

    // upload button
    if (this.uploadBtn) {
      this.uploadBtn.addEventListener("click", () => this.uploadQueue());
    }
  }

  /* =========================
     MODE LOGIC
  ========================= */

  applyWorkflowRules() {
    // ici seulement si besoin futur (dossiers)
    // ex: disable upload selon règles métier
  }

  enable() {
    if (this.uploadBtn) {
      this.uploadBtn.disabled = false;
    }

    this.el.classList.remove("disabled");
  }

  disable() {
    if (this.uploadBtn) {
      this.uploadBtn.disabled = true;
    }

    this.el.classList.add("disabled");
  }

  canUpload() {
    // MODE MEDIA = toujours OK
    if (this.mode === "media") {
      return true;
    }

    // MODE WORKFLOW (dossiers)
    const allowed = [
      "draft",
      "vehicle_selected",
      "documents_pending",
      "documents_review"
    ];

    return allowed.includes(this.status);
  }

  /* =========================
     FILE HANDLING
  ========================= */

  addToQueue(files) {
    Array.from(files).forEach(file => {
      const key = `${file.name}_${file.size}`;

      if (this.fileKeys.has(key)) return;

      this.fileKeys.add(key);
      this.filesQueue.push(file);

      if (this.previewContainer) {
        this.previewContainer.appendChild(this.createLocalThumb(file));
      }
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

    if (this.destination) {
      formData.append("destination", this.destination);
    }

    await fetch(this.uploadUrl, {
      method: "POST",
      body: formData
    });

    this.filesQueue = [];
    this.fileKeys.clear();

    await this.refreshState?.();
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

  /* =========================
     SERVER
  ========================= */

  async refreshState() {
    if (!this.documentsUrl || !this.previewContainer) return;

    const res = await fetch(this.documentsUrl);
    const data = await res.json();

    this.renderDocuments(data.documents || []);
  }

  renderDocuments(files) {
    if (!Array.isArray(files)) return;

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
    `;

    const btn = document.createElement("button");
    btn.classList.add("dz-delete");
    btn.textContent = "×";

    btn.addEventListener("click", async e => {
      e.stopPropagation();

      if (!confirm("Supprimer ce fichier ?")) return;

      const url = this.deleteUrlTemplate.replace("__id__", file.id);

      await fetch(url, { method: "DELETE" });

      this.refreshState?.();
    });

    div.appendChild(btn);

    return div;
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
}
