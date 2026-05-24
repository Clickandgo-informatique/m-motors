document.addEventListener("DOMContentLoaded", () => {
    console.log("js dossier financing initialisé");
  const typeField = document.querySelector("#dossier_financing_type");
  const leasingWrapper = document.querySelector("#leasing-type-wrapper");

  if (!typeField || !leasingWrapper) {
    return;
  }

  const toggle = () => {
    leasingWrapper.style.display = typeField.value === "leasing" ? "block" : "none";
  };

  typeField.addEventListener("change", toggle);
  toggle();
});
