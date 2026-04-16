export default class Dropzone {
  constructor(element) {
    this.el = element;

    this.uploadUrl = element.dataset.uploadUrl;
    this.deleteUrl = element.dataset.deleteUrl;
    this.destination = element.dataset.destination;
    this.previewId = element.dataset.previewId;
    this.inputName = element.dataset.inputName || "documents_json";

    this.previewContainer = document.querySelector(`#${this.previewId}`);
    this.hiddenField = document.querySelector(`#${this.inputName}`);

    this.init();
  }

  init() {
    this.input = document.createElement("input");
    this.input.type = "file";
    this.input.multiple = true;
    this.input.name = "file[]";
    this.input.classList.add("d-none");

    this.el.appendChild(this.input);

    this.el.addEventListener("click", e => {
      if (!e.target.classList.contains("dz-delete")) {
        this.input.click();
      }
    });

    this.el.addEventListener("dragover", e => e.preventDefault());
    this.el.addEventListener("drop", e => this.onDrop(e));

    this.input.addEventListener("change", () => {
      this.uploadFiles(this.input.files);
    });

    this.previewContainer?.addEventListener("click", e => {
      if (e.target.classList.contains("dz-delete")) {
        this.deleteFile(e.target.dataset.file, e.target.closest(".dz-thumb"));
      }
    });

    this.updateCounter();
  }

  onDrop(e) {
    e.preventDefault();
    this.uploadFiles(e.dataTransfer.files);
  }

  async uploadFiles(files) {
    const formData = new FormData();

    for (let file of files) {
      formData.append("file[]", file);
    }

    const response = await fetch(this.uploadUrl, {
      method: "POST",
      body: formData
    });

    const data = await response.json();

    if (!data.documents) {
      console.error("Upload error:", data);
      return;
    }

    this.renderPreview(data.documents);
    this.updateHiddenField(data.documents);
    this.updateCounter();
  }

  renderPreview(documents) {
    documents.forEach(doc => {
      const div = document.createElement("div");
      div.classList.add("dz-thumb");
      div.dataset.file = doc.fileName;

      div.innerHTML = `
                <img src="/uploads/${doc.path}"
                     width="150"
                     class="rounded border me-2 mb-2">

                <button class="dz-delete" data-file="${doc.fileName}">
                    ×
                </button>
            `;

      this.previewContainer.appendChild(div);
    });
  }

  updateHiddenField(documents) {
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

  async deleteFile(documentId, thumbElement) {
    const response = await fetch(this.deleteUrl.replace("__id__", documentId), {
      method: "DELETE"
    });

    if (!response.ok) {
      console.error("Delete failed");
      return;
    }

    thumbElement.classList.add("dz-removing");

    setTimeout(() => {
      thumbElement.remove();

      const field = document.querySelector("#images-field");
      const current = JSON.parse(field.value || "[]");

      field.value = JSON.stringify(current.filter(f => f.id != documentId));

      this.updateCounter();
    }, 200);
  }

  updateCounter() {
    const counter = this.el.parentElement.querySelector(".dz-counter");

    if (!counter || !this.previewContainer) return;

    const count = this.previewContainer.querySelectorAll(".dz-thumb").length;

    counter.textContent =
      count === 0
        ? "0 fichier"
        : count + (count > 1 ? " fichiers" : " fichier");
  }
}

// AUTO INIT
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".dropzone").forEach(el => {
    new Dropzone(el);
  });
});
