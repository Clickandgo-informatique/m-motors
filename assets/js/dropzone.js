export default class Dropzone {
    constructor(element) {
        this.el = element;
        this.uploadUrl = element.dataset.uploadUrl;
        this.deleteUrl = element.dataset.deleteUrl;
        this.type = element.dataset.type;
        this.destination = element.dataset.destination;
        this.previewId = element.dataset.previewId;

        this.previewContainer = document.querySelector(`#${this.previewId}`);

        this.init();
    }

    init() {
        this.input = document.createElement("input");
        this.input.type = "file";
        this.input.multiple = true;
        this.input.name = "file[]";
        this.input.classList.add("d-none");
        this.el.appendChild(this.input);

        this.el.addEventListener("click", (e) => {
            if (!e.target.classList.contains("dz-delete")) {
                this.input.click();
            }
        });

        this.el.addEventListener("dragover", (e) => e.preventDefault());
        this.el.addEventListener("drop", (e) => this.onDrop(e));

        this.input.addEventListener("change", () =>
            this.uploadFiles(this.input.files),
        );

        this.previewContainer.addEventListener("click", (e) => {
            if (e.target.classList.contains("dz-delete")) {
                this.deleteFile(
                    e.target.dataset.file,
                    e.target.closest(".dz-thumb"),
                );
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
        for (let file of files) formData.append("file[]", file);
        formData.append("destination", this.destination);

        const response = await fetch(this.uploadUrl, {
            method: "POST",
            body: formData,
        });

        const data = await response.json();

        this.renderPreview(data.urls);
        this.updateHiddenField(data.urls);
        this.updateCounter();
    }

    renderPreview(urls) {
        urls.forEach((urlObj) => {
            const div = document.createElement("div");
            div.classList.add("dz-thumb");
            div.dataset.file = urlObj.jpg;

            div.innerHTML = `
                <img src="/uploads/${this.destination}/${urlObj.jpg_thumb}"
                     width="150"
                     class="rounded border me-2 mb-2">
                <button class="dz-delete" data-file="${urlObj.jpg}">×</button>
            `;

            this.previewContainer.appendChild(div);
        });
    }

    updateHiddenField(urls) {
        const field = document.querySelector("#images-field");
        const current = field.value ? JSON.parse(field.value) : [];
        field.value = JSON.stringify([...current, ...urls]);
    }

    async deleteFile(filename, thumbElement) {
        const body = new URLSearchParams();
        body.append("filename", filename);
        body.append("destination", this.destination);

        await fetch(this.deleteUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body,
        });

        thumbElement.classList.add("dz-removing");
        // Suppression réelle + mise à jour du compteur APRÈS
        setTimeout(() => {
            thumbElement.remove();
            const field = document.querySelector("#images-field");
            const current = JSON.parse(field.value);
            field.value = JSON.stringify(
                current.filter((f) => f.jpg !== filename),
            );
            this.updateCounter();
        }, 250);
    }

    updateCounter() {
        const counter = this.el.parentElement.querySelector(".dz-counter");
        if (!counter || !this.previewContainer) return;

        const count =
            this.previewContainer.querySelectorAll(".dz-thumb").length;

        if (count === 0) {
            counter.textContent = "0 image";
        } else {
            counter.textContent = count + (count > 1 ? " images" : " image");
        }
    }
}

// 🔥 AUTO-INIT — indispensable
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".dropzone").forEach((el) => new Dropzone(el));
});
