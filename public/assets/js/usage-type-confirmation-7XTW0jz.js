export function initVehicleUsageTypeConfirmation(root = document) {
    const forms = root.querySelectorAll("form");

    forms.forEach(form => {
        if (form.dataset.usageBound === "1") {
            return;
        }

        const getCheckedValue = () => {
            const checked = form.querySelector('input[type="radio"]:checked');
            return checked ? checked.value : null;
        };

        let initialValue = getCheckedValue();

        form.addEventListener("submit", e => {
            const currentValue = getCheckedValue();

            if (initialValue !== null && currentValue !== initialValue) {
                const confirmChange = window.confirm(
                    "Changer le type d'utilisation du véhicule peut impacter les dossiers associés. Confirmer ?"
                );

                if (!confirmChange) {
                    e.preventDefault();
                }
            }
        });

        form.dataset.usageBound = "1";
    });
}
