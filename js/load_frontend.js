import PkpLoad from '../lib/pkp/js/load_frontend.js';
window.pkp = Object.assign(PkpLoad, window.pkp || {});

document.addEventListener('DOMContentLoaded', () => {
	// pkp-modal-manager:
	const div = document.createElement('div');
	div.setAttribute('data-vue-root', '');
	const modalManager = document.createElement('pkp-modal-manager');
	div.appendChild(modalManager);
	document.body.prepend(div);
	pkp.registry.initVueFromAttributes();
});
