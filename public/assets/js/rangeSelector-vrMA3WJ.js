const minInput = document.getElementById('mileage-min');
const maxInput = document.getElementById('mileage-max');

const minDisplay = document.getElementById('mileage-min-value');
const maxDisplay = document.getElementById('mileage-max-value');

const STEP = 10000;
const MIN = 0;
const MAX = 400000;

function format(value) {
	return Number(value).toLocaleString('fr-FR');
}

function updateValues() {
	let min = parseInt(minInput.value);
	let max = parseInt(maxInput.value);

	// Empêche min de dépasser max
	if (min > max - STEP) {
		min = Math.max(MIN, max - STEP);
		minInput.value = min;
	}

	// Empêche max d'être trop bas
	if (max < min + STEP) {
		max = Math.min(MAX, min + STEP);
		maxInput.value = max;
	}

	//Sécurité absolue
	min = Math.max(MIN, min);
	max = Math.min(MAX, max);

	minDisplay.textContent = format(min);
	maxDisplay.textContent = format(max);

	triggerFilter();
}

minInput.addEventListener('input', updateValues);
maxInput.addEventListener('input', updateValues);

updateValues();