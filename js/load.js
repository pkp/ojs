// Vue lib with custom mixins
import Vue from '../lib/pkp/js/classes/VueInit.js';

// Helper for initializing and tracking Vue controllers
import VueRegistry from '../lib/pkp/js/classes/VueRegistry.js';

// All Vue controllers
import ListPanel from '../lib/pkp/js/controllers/list/ListPanel.vue';

// Expose Vue, the registry and controllers in a global var
window.pkp = {
	vue: Vue,
	registry: VueRegistry,
	controllers: {
		'ListPanel': ListPanel,
	},
};
