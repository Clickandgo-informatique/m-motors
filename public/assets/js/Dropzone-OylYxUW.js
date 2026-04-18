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
    // =========================
    // INPUT FILE (hidden)
    // =========================
    this.input = document.createElement("input");
    this.input.type = "file";
    this.input.multiple = true;
    this.input.classList.add("d-none");
    this.el.appendChild(this.input);

    // =========================
    // CLICK ZONE → OPEN FILE PICKER
    // =========================
    this.el.addEventListener("click", e => {
      if (!e.target.classList.contains("dz-delete")) {
        this.input.click();
      }
    });

    // =========================
    // DRAG & DROP
    // =========================
    this.el.addEventListener("dragover", e => e.preventDefault());

    this.el.addEventListener("drop", e => {
      e.preventDefault();
      this.addToQueue(e.dataTransfer.files);
    });

    // =========================
    // INPUT CHANGE
    // =========================
    this.input.addEventListener("change", e => {
      this.addToQueue(e.target.files);
    });

    // =========================
    // UPLOAD BUTTON
    // =========================
    if (this.uploadBtn) {
      this.uploadBtn.addEventListener("click", () => this.uploadQueue());
    }

    // =========================
    // DELETE EXISTING FILES
    // =========================
    this.previewContainer.addEventListener("click", e => {
      if (e.target.classList.contains("dz-delete")) {
        this.deleteFile(e.target.dataset.id, e.target.closest(".dz-thumb"));
      }
    });

    this.updateButtonState();
  }

  // =========================================================
  // ADD FILES TO QUEUE + PREVIEW
  // =========================================================
  addToQueue(files) {
    const arr = Array.from(files);

    arr.forEach(file => {
      this.filesQueue.push(file);

      const div = document.createElement("div");
      div.classList.add("dz-thumb", "dz-staging");

      let previewContent = "";

      // =========================
      // IMAGE PREVIEW (REAL)
      // =========================
      if (file.type.startsWith("image/")) {
        const url = URL.createObjectURL(file);

        previewContent = `
          <img src="${url}" alt="${file.name}">
        `;
      }

      // =========================
      // PDF
      // =========================
      else if (file.type === "application/pdf") {
        previewContent = `
          <div class="dz-file-preview dz-pdf">PDF</div>
        `;
      }

      // =========================
      // OFFICE FILES
      // =========================
      else if (
        file.type.includes("word") ||
        file.name.endsWith(".doc") ||
        file.name.endsWith(".docx")
      ) {
        previewContent = `
          <div class="dz-file-preview dz-word">WORD</div>
        `;
      } else if (
        file.type.includes("excel") ||
        file.name.endsWith(".xls") ||
        file.name.endsWith(".xlsx")
      ) {
        previewContent = `
          <div class="dz-file-preview dz-excel">EXCEL</div>
        `;
      }

      // =========================
      // GENERIC
      // =========================
      else {
        previewContent = `
          <div class="dz-file-preview dz-generic">
            ${file.name
              .split(".")
              .pop()
              .toUpperCase()}
          </div>
        `;
      }

      div.innerHTML = `
        ${previewContent}
        <div class="dz-filename">${file.name}</div>
      `;

      this.previewContainer.appendChild(div);
    });

    this.updateButtonState();
  }

  // =========================================================
  // UPLOAD FILES
  // =========================================================
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

  // =========================================================
  // RENDER UPLOADED FILES (SERVER SIDE)
  // =========================================================
  renderUploaded(files) {
    this.previewContainer.innerHTML = "";

    files.forEach(file => {
      const div = document.createElement("div");
      div.classList.add("dz-thumb");
      div.dataset.id = file.id;

      let content = "";

      const ext = file.fileName
        .split(".")
        .pop()
        .toLowerCase();

      if (["jpg", "jpeg", "png", "webp"].includes(ext)) {
        content = `<img src="/uploads/${file.path}" />`;
      } else if (ext === "pdf") {
        content = `<div class="dz-file-preview dz-pdf">PDF</div>`;
      } else if (["doc", "docx"].includes(ext)) {
        content = `<div class="dz-file-preview dz-word">WORD</div>`;
      } else if (["xls", "xlsx"].includes(ext)) {
        content = `<div class="dz-file-preview dz-excel">EXCEL</div>`;
      } else {
        content = `<div class="dz-file-preview dz-generic">FILE</div>`;
      }

      div.innerHTML = `
        ${content}
        <div class="dz-filename">${file.fileName}</div>
        <button class="dz-delete" data-id="${file.id}">×</button>
      `;

      this.previewContainer.appendChild(div);
    });
  }

  // =========================================================
  // DELETE FILE
  // =========================================================
  async deleteFile(id, element) {
    const url = this.deleteUrlTemplate.replace("__id__", id);

    await fetch(url, { method: "DELETE" });

    element.classList.add("dz-removing");

    setTimeout(() => element.remove(), 200);

    this.updateButtonState();
  }

  // =========================================================
  // BUTTON STATE
  // =========================================================
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
