export default class Dropzone {
  constructor(element) {
    this.el = element;

    this.uploadUrl = element.dataset.uploadUrl;
    this.deleteUrlTemplate = element.dataset.deleteUrl;
    this.documentsUrl = element.dataset.documentsUrl;
    this.destination = element.dataset.destination;

    this.previewContainer = document.querySelector(
      `#${element.dataset.previewId}`
    );

    this.uploadBtn = document.querySelector(".dz-upload-btn");

    this.filesQueue = [];
    this.fileKeys = new Set();

    this.init();
    this.loadDocuments();
  }

  // =========================================================
  // INIT
  // =========================================================
  init() {
    this.input = document.createElement("input");
    this.input.type = "file";
    this.input.multiple = true;
    this.input.classList.add("d-none");
    this.el.appendChild(this.input);

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

  // =========================================================
  // LOAD FROM SERVER (STATE SOURCE)
  // =========================================================
  async loadDocuments() {
    const res = await fetch(this.documentsUrl);
    const data = await res.json();

    this.renderDocuments(data.documents); // 🔥 FIX IMPORTANT
  }

  // =========================================================
  // RENDER
  // =========================================================
  renderDocuments(files) {
    if (!Array.isArray(files)) {
      console.error("Dropzone: invalid data format", files);
      return;
    }

    this.previewContainer.innerHTML = "";

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

      div.innerHTML = `
      <div class="dz-preview">${preview}</div>

      <div class="dz-filename">${file.originalName ?? file.fileName}</div>

      <div class="dz-meta">
        Envoyé le ${file.createdAt ?? ""}
      </div>

      <div class="mt-2">
        <span class="badge bg-${file.badge ?? "secondary"}">
          ${file.statusLabel ?? "unknown"}
        </span>
      </div>
    `;

      // ========================= DELETE =========================
      const btn = document.createElement("button");
      btn.classList.add("dz-delete");
      btn.textContent = "×";

      btn.addEventListener("click", async e => {
        e.stopPropagation();

        if (!confirm("Supprimer ce fichier ?")) return;

        const url = this.deleteUrlTemplate.replace("__id__", file.id);

        await fetch(url, { method: "DELETE" });

        this.loadDocuments();
      });

      div.appendChild(btn);

      this.previewContainer.appendChild(div);
    });
  }
  // =========================================================
  // QUEUE
  // =========================================================
  addToQueue(files) {
    Array.from(files).forEach(file => {
      const key = `${file.name}_${file.size}`;

      if (this.fileKeys.has(key)) return;

      this.fileKeys.add(key);
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

    // reset queue + sync server state
    this.filesQueue = [];
    this.fileKeys.clear();

    this.loadDocuments();
  }

  // =========================================================
  // UI
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
