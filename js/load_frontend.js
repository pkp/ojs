// styles
import '@/frontend/styles/pkp-variables.css';
import '@/frontend/styles/style.css';

import PkpLoad from '../lib/pkp/js/load_frontend.js';
import {usePageStore} from '../lib/ui-library/src/frontend/stores/pkpPageStore.js';
window.pkp = Object.assign(PkpLoad, window.pkp || {});

document.addEventListener('DOMContentLoaded', () => {
	const pageStore = usePageStore();
	if (pkp?._piniaData) {
		pageStore.setData(pkp._piniaData);
	}

	// pkp-modal-manager:
	const div = document.createElement('div');
	div.setAttribute('data-vue-root', '');
	const modalManager = document.createElement('pkp-modal-manager');
	div.appendChild(modalManager);
	document.body.prepend(div);
	pkp.registry.initVueFromAttributes();
});
