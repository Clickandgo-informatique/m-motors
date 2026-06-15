document.addEventListener("DOMContentLoaded", () => {
  const acceptButton = document.getElementById("cookie-accept-btn");
  const refuseButton = document.getElementById("cookie-refuse-btn");

  if (acceptButton) {
    acceptButton.addEventListener("click", async () => {
      const response = await fetch("/cookies/accept", {
        method: "POST"
      });

      if (response.ok) {
        document.querySelector(".cookie-banner")?.remove();
      }
    });
  }

  if (refuseButton) {
    refuseButton.addEventListener("click", async () => {
      const response = await fetch("/cookies/refuse", {
        method: "POST"
      });

      if (response.ok) {
        document.querySelector(".cookie-banner")?.remove();
      }
    });
  }
});
