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
  }

  /* INPUT */

  createInput() {
    this.input = document.createElement("input");
    this.input.type = "file";
    this.input.multiple = true;
    this.input.accept = "image/*";
    this.input.style.display = "none";

    this.el.appendChild(this.input);
  }

  /* EVENTS */

  bindEvents() {
    this.el.addEventListener("click", e => {
      if (
        e.target.closest(".dz-upload-btn") ||
        e.target.closest(".dz-delete")
      ) {
        return;
      }

      if (!this.input) return;

      this.input.click();
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
      this.uploadBtn.addEventListener("click", e => {
        e.preventDefault();
        this.uploadQueue();
      });
    }
  }

  /* QUEUE */

  addToQueue(files) {
    if (!files || !files.length) return;

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

  removeFromQueue(file) {
    const key = `${file.name}_${file.size}`;

    this.fileKeys.delete(key);

    this.filesQueue = this.filesQueue.filter(f => {
      return !(f.name === file.name && f.size === file.size);
    });

    this.updateButtonState();
  }

  createLocalThumb(file) {
    const div = document.createElement("div");
    div.classList.add("dz-thumb", "dz-staging");

    const wrapper = document.createElement("div");
    wrapper.classList.add("dz-preview");

    const meta = document.createElement("div");
    meta.classList.add("dz-meta");
    meta.textContent = file.name;

    const btn = document.createElement("button");
    btn.type = "button";
    btn.classList.add("dz-delete");
    btn.textContent = "×";

    btn.addEventListener("click", e => {
      e.stopPropagation();
      this.removeFromQueue(file);
      div.remove();
    });

    const ext = file.name
      .split(".")
      .pop()
      .toLowerCase();

    if (["jpg", "jpeg", "png", "webp"].includes(ext)) {
      const img = document.createElement("img");
      img.classList.add("dz-img");

      const reader = new FileReader();
      reader.onload = e => {
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);

      wrapper.appendChild(img);
    } else {
      wrapper.textContent = ext.toUpperCase();
    }

    div.appendChild(wrapper);
    div.appendChild(meta);
    div.appendChild(btn);

    return div;
  }

  /* UPLOAD */

  async uploadQueue() {
    if (!this.filesQueue.length) return;

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

      const text = await response.text();

      if (!response.ok) {
        throw new Error(text);
      }

      this.filesQueue = [];
      this.fileKeys.clear();

      this.updateButtonState();

      await this.refreshState?.();
    } catch (e) {
      console.error("[Dropzone] upload error", e);
    }
  }

  /* UI */

  updateButtonState() {
    if (!this.uploadBtn) return;

    const hasFiles = this.filesQueue.length > 0;

    this.uploadBtn.disabled = !hasFiles;

    this.uploadBtn.textContent = hasFiles
      ? `Envoyer (${this.filesQueue.length})`
      : "Envoyer les fichiers";
  }

  /* SERVER */

  async refreshState() {
    if (!this.documentsUrl || !this.previewContainer) return;

    try {
      const res = await fetch(this.documentsUrl);
      const data = await res.json();

      this.renderDocuments(data.documents || []);
    } catch (e) {
      console.error(e);
    }
  }

  renderDocuments(files) {
    this.previewContainer.innerHTML = "";

    files.forEach(file => {
      this.previewContainer.appendChild(this.createServerThumb(file));
    });
  }

  createServerThumb(file) {
    const div = document.createElement("div");
    div.classList.add("dz-thumb");

    const wrapper = document.createElement("div");
    wrapper.classList.add("dz-preview");
    wrapper.textContent = file.fileName;

    const meta = document.createElement("div");
    meta.classList.add("dz-meta");
    meta.textContent = file.originalName || "";

    const btn = document.createElement("button");
    btn.type = "button";
    btn.classList.add("dz-delete");
    btn.textContent = "×";

    btn.addEventListener("click", async e => {
      e.stopPropagation();

      if (!confirm("Supprimer ce fichier ?")) return;

      const url = this.deleteUrlTemplate.replace("__id__", file.id);

      await fetch(url, { method: "DELETE" });

      this.refreshState();
    });

    div.appendChild(wrapper);
    div.appendChild(meta);
    div.appendChild(btn);

    return div;
  }

  /* WORKFLOW */

  applyWorkflowRules() {
    console.log("[Dropzone] workflow mode");
  }
}
