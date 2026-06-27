function initDossierFinancingToggle(root = document) {
    const financingType = root.querySelector('[id$="_financing_type"]');
    const leasingWrapper = root.querySelector("#leasing-type-wrapper");

    if (!financingType || !leasingWrapper) return;

    function toggle() {
        leasingWrapper.style.display = financingType.value === "leasing" ? "block" : "none";
    }

    financingType.addEventListener("change", toggle);
    toggle();
}

document.addEventListener("DOMContentLoaded", () => {
    initDossierFinancingToggle();
});

window.initDossierFinancingToggle = initDossierFinancingToggle;
