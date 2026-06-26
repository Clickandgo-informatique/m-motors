export function initVehicleUsageTypeConfirmation(root = document) {

    const forms = root.querySelectorAll('form');

    forms.forEach((form) => {

        if (form.dataset.usageBound === "1") {
            return;
        }

        const radios = form.querySelectorAll('input[type="radio"]');

        if (!radios.length) return;

        let initialValue = form.querySelector('input[type="radio"]:checked')?.value;

        radios.forEach((radio) => {

            radio.addEventListener('change', (e) => {

                const newValue = e.target.value;

                if (initialValue && newValue !== initialValue) {

                    const confirmChange = window.confirm(
                        "Changer le type d'utilisation du véhicule peut impacter les dossiers associés. Confirmer ?"
                    );

                    if (!confirmChange) {
                        // rollback visuel
                        const previous = form.querySelector(
                            `input[type="radio"][value="${initialValue}"]`
                        );

                        if (previous) {
                            previous.checked = true;
                        }

                        return;
                    }

                    // accepter changement
                    initialValue = newValue;
                }
            });
        });

        form.dataset.usageBound = "1";
    });
}