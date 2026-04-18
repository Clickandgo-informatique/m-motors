export default class Dropzone {
  constructor(element) {
    this.el = element;

    this.uploadUrl = element.dataset.uploadUrl;
    this.deleteUrlTemplate = element.dataset.deleteUrl;
    this.destination = element.dataset.destination;

    this.previewContainer = document.querySelector(
      `#${element.dataset.previewId}`
    );

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
    // OPEN FILE PICKER
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
    // DELETE SERVER FILES
    // =========================
    this.previewContainer.addEventListener("click", async e => {
      if (e.target.classList.contains("dz-delete")) {
        const id = e.target.dataset.id;
        const el = e.target.closest(".dz-thumb");

        if (!confirm("Supprimer ce fichier ?")) return;

        const url = this.deleteUrlTemplate.replace("__id__", id);
        await fetch(url, { method: "DELETE" });

        el.remove();
      }
    });

    this.updateButtonState();
  }

  // =========================================================
  // ADD FILES (CLIENT SIDE PREVIEW)
  // =========================================================
  addToQueue(files) {
    Array.from(files).forEach(file => {
      this.filesQueue.push(file);

      const div = document.createElement("div");
      div.classList.add("dz-thumb", "dz-staging");

      let preview = "";

      // IMAGE
      if (file.type.startsWith("image/")) {
        const url = URL.createObjectURL(file);
        preview = `<img src="${url}">`;
      }

      // PDF
      else if (file.type === "application/pdf") {
        preview = `<div class="dz-file-preview dz-pdf">PDF</div>`;
      }

      // WORD
      else if (file.name.match(/\.(doc|docx)$/)) {
        preview = `<div class="dz-file-preview dz-word">WORD</div>`;
      }

      // EXCEL
      else if (file.name.match(/\.(xls|xlsx)$/)) {
        preview = `<div class="dz-file-preview dz-excel">EXCEL</div>`;
      }

      // GENERIC
      else {
        preview = `
          <div class="dz-file-preview dz-generic">
            ${file.name
              .split(".")
              .pop()
              .toUpperCase()}
          </div>
        `;
      }

      const now = new Date().toLocaleString();

      div.innerHTML = `
        <div class="dz-preview">${preview}</div>
        <div class="dz-filename">${file.name}</div>
        <div class="dz-meta">Ajouté le ${now}</div>
      `;

      // DELETE LOCAL
      const btn = document.createElement("button");
      btn.classList.add("dz-delete");
      btn.textContent = "×";

      btn.addEventListener("click", e => {
        e.stopPropagation();

        this.filesQueue = this.filesQueue.filter(f => f !== file);
        div.remove();

        this.updateButtonState();
      });

      div.appendChild(btn);
      this.previewContainer.appendChild(div);
    });

    this.updateButtonState();
  }

  // =========================================================
  // UPLOAD FILES
  // =========================================================
  async uploadQueue() {
    if (!this.filesQueue.length) return;

    const formData = new FormData();

    this.filesQueue.forEach(file => {
      formData.append("file[]", file);
    });

    formData.append("destination", this.destination);

    const res = await fetch(this.uploadUrl, {
      method: "POST",
      body: formData
    });

    const data = await res.json();

    this.renderUploaded(data.documents || []);

    this.filesQueue = [];
    this.updateButtonState();
  }

  // =========================================================
  // RENDER SERVER FILES
  // =========================================================
  renderUploaded(files) {
    files.forEach(file => {
      const div = document.createElement("div");
      div.classList.add("dz-thumb");
      div.dataset.id = file.id;

      const ext = file.fileName
        .split(".")
        .pop()
        .toLowerCase();

      let preview = "";

      if (["jpg", "jpeg", "png", "webp"].includes(ext)) {
        preview = `<img src="/uploads/${file.path}">`;
      } else if (ext === "pdf") {
        preview = `<div class="dz-file-preview dz-pdf">PDF</div>`;
      } else if (["doc", "docx"].includes(ext)) {
        preview = `<div class="dz-file-preview dz-word">WORD</div>`;
      } else if (["xls", "xlsx"].includes(ext)) {
        preview = `<div class="dz-file-preview dz-excel">EXCEL</div>`;
      } else {
        preview = `<div class="dz-file-preview dz-generic">FILE</div>`;
      }

      const now = new Date().toLocaleString();

      div.innerHTML = `
        <div class="dz-preview">${preview}</div>
        <div class="dz-filename">${file.fileName}</div>
        <div class="dz-meta">Envoyé le ${now}</div>
      `;

      const btn = document.createElement("button");
      btn.classList.add("dz-delete");
      btn.textContent = "×";
      btn.dataset.id = file.id;

      btn.addEventListener("click", async e => {
        e.stopPropagation();

        if (!confirm("Supprimer ce fichier ?")) return;

        const url = this.deleteUrlTemplate.replace("__id__", file.id);
        await fetch(url, { method: "DELETE" });

        div.remove();
      });

      div.appendChild(btn);
      this.previewContainer.appendChild(div);
    });
  }

  // =========================================================
  // UI STATE
  // =========================================================
  updateButtonState() {
    if (!this.uploadBtn) return;

    const hasFiles = this.filesQueue.length > 0;

    this.uploadBtn.disabled = !hasFiles;
    this.uploadBtn.textContent = hasFiles
      ? `Envoyer (${this.filesQueue.length})`
      : "Envoyer les fichiers";
  }
}
