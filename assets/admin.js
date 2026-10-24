(function () {
	'use strict';
	const root = document.querySelector('.translit-it-settings');
	if (!root) return;
	const form = root.querySelector('form');
	const modes = Array.from(root.querySelectorAll('input[name="rtl_standard"]'));
	const panel = root.querySelector('[data-cloud-panel]');
	const key = root.querySelector('#ya_api_key');
	const keyToggle = root.querySelector('[data-key-toggle]');
	const clearKey = root.querySelector('[data-clear-key]');
	const saveStatus = root.querySelector('[data-save-status]');
	function refreshMode() {
		const selected = modes.find(function (input) { return input.checked; });
		if (!selected) return;
		panel.hidden = selected.value !== 'cloud_yandex' && selected.value !== 'yandex';
		root.querySelector('[data-cloud-help]').hidden = selected.value !== 'cloud_yandex';
		root.querySelector('[data-legacy-help]').hidden = selected.value !== 'yandex';
		modes.forEach(function (input) {
			const card = input.closest('.translit-it-mode');
			if (card) card.classList.toggle('is-selected', input.checked);
		});
		root.querySelector('[data-preview-mode]').textContent = selected.dataset.modeTitle;
		root.querySelector('[data-preview-result]').textContent = selected.dataset.preview;
		root.querySelector('[data-preview-note-output]').textContent = selected.dataset.previewNote;
	}
	function markDirty() {
		saveStatus.textContent = saveStatus.dataset.dirtyLabel;
		saveStatus.classList.add('is-dirty');
	}
	modes.forEach(function (input) { input.addEventListener('change', refreshMode); });
	keyToggle.hidden = false;
	keyToggle.addEventListener('click', function () {
		const reveal = key.type === 'password';
		key.type = reveal ? 'text' : 'password';
		keyToggle.textContent = reveal ? keyToggle.dataset.hideLabel : keyToggle.dataset.showLabel;
		keyToggle.setAttribute('aria-pressed', String(reveal));
	});
	if (clearKey) {
		clearKey.addEventListener('change', function () {
			key.disabled = clearKey.checked;
			keyToggle.disabled = clearKey.checked;
		});
	}
	form.addEventListener('change', markDirty);
	key.addEventListener('input', markDirty);
	refreshMode();
}());
