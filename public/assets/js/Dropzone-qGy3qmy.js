export default class Dropzone {
  constructor(element) {
    this.el = element;

    // URLs backend
    this.uploadUrl = element.dataset.uploadUrl;
    this.deleteUrlTemplate = element.dataset.deleteUrl;
    this.documentsUrl = element.dataset.documentsUrl;

    // contexte métier
    this.destination = element.dataset.destination || null;
    this.mode = element.dataset.mode || "media";

    // DOM
    this.previewContainer = document.getElementById(element.dataset.previewId);
    console.log("[Dropzone] previewContainer:", this.previewContainer);

    this.uploadBtn = this.el.querySelector(".dz-upload-btn");

    // state
    this.input = null;
    this.filesQueue = [];
    this.fileKeys = new Set();

    this.init();
  }

  init() {
    this.createInput();
    this.bindEvents();

    this.updateButtonState();

    if (this.mode === "workflow") {
      this.applyWorkflowRules();
    }

    console.log("[Dropzone] init", {
      mode: this.mode,
      uploadUrl: this.uploadUrl
    });
  }

  /* =========================================================
   * INPUT
   * ========================================================= */

  createInput() {
    this.input = document.createElement("input");

    this.input.type = "file";
    this.input.multiple = true;
    this.input.accept = "image/*";

    // important : invisible fiable
    this.input.style.display = "none";
    this.el.appendChild(this.input);

    console.log("[Dropzone] input created", this.input);
  }

  /* =========================================================
   * EVENTS
   * ========================================================= */

  bindEvents() {
    // ouverture file picker
    this.el.addEventListener("click", e => {
      if (
        e.target.closest(".dz-upload-btn") ||
        e.target.closest(".dz-delete")
      ) {
        return;
      }

      if (!this.input) {
        console.error("[Dropzone] input missing");
        return;
      }

      console.log("[Dropzone] open file picker");

      this.input.click();
    });

    // drag & drop
    this.el.addEventListener("dragover", e => e.preventDefault());

    this.el.addEventListener("drop", e => {
      e.preventDefault();

      console.log("[Dropzone] drop event");

      this.addToQueue(e.dataTransfer.files);
    });

    // file selection
    this.input.addEventListener("change", e => {
      console.log("[Dropzone] change event", e.target.files);

      this.addToQueue(e.target.files);

      this.input.value = "";
    });

    // upload button
    if (this.uploadBtn) {
      this.uploadBtn.addEventListener("click", e => {
        e.preventDefault();

        console.log("[Dropzone] upload button clicked");

        this.uploadQueue();
      });
    } else {
      console.warn("[Dropzone] upload button not found");
    }
  }

  /* =========================================================
   * QUEUE
   * ========================================================= */

  addToQueue(files) {
    if (!files || !files.length) {
      console.warn("[Dropzone] no files received");
      return;
    }

    Array.from(files).forEach(file => {
      const key = `${file.name}_${file.size}`;

      if (this.fileKeys.has(key)) return;

      this.fileKeys.add(key);
      this.filesQueue.push(file);

      if (this.previewContainer) {
        this.previewContainer.appendChild(this.createLocalThumb(file));
      }
    });

    console.log("[Dropzone] queue updated", this.filesQueue);

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

  /* =========================================================
   * UPLOAD
   * ========================================================= */

  async uploadQueue() {
    if (!this.filesQueue.length) {
      console.warn("[Dropzone] empty queue");
      return;
    }

    console.log("[Dropzone] uploading", this.filesQueue);

    const formData = new FormData();

    this.filesQueue.forEach(file => {
      formData.append("files[]", file);
    });

    if (this.destination) {
      formData.append("destination", this.destination);
    }

    try {
      const response = await fetch(this.uploadUrl, {
        method: "POST",
        body: formData
      });

      console.log("[Dropzone] response status", response.status);

      const text = await response.text();
      console.log("[Dropzone] response body", text);

      if (!response.ok) {
        throw new Error("Upload failed");
      }

      this.filesQueue = [];
      this.fileKeys.clear();

      this.updateButtonState();

      await this.refreshState?.();
    } catch (e) {
      console.error("[Dropzone] upload error", e);
    }
  }

  /* =========================================================
   * UI
   * ========================================================= */

  updateButtonState() {
    if (!this.uploadBtn) return;

    const hasFiles = this.filesQueue.length > 0;

    this.uploadBtn.disabled = !hasFiles;

    this.uploadBtn.textContent = hasFiles
      ? `Envoyer (${this.filesQueue.length})`
      : "Envoyer les fichiers";
  }

  /* =========================================================
   * WORKFLOW (optionnel)
   * ========================================================= */

  applyWorkflowRules() {
    console.log("[Dropzone] workflow mode active");
  }

  /* =========================================================
   * SERVER
   * ========================================================= */

  async refreshState() {
    if (!this.documentsUrl || !this.previewContainer) return;

    try {
      const res = await fetch(this.documentsUrl);
      const data = await res.json();

      this.renderDocuments(data.documents || []);
    } catch (e) {
      console.error("[Dropzone] refresh error", e);
    }
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

    div.innerHTML = `
      <div class="dz-preview">
        ${file.fileName}
      </div>
      <div class="dz-filename">
        ${file.originalName ?? ""}
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
}
