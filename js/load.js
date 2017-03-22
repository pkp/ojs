// Vue lib with custom mixins
import Vue from '../lib/pkp/js/classes/VueInit.js';

// Helper for initializing and tracking Vue controllers
import VueRegistry from '../lib/pkp/js/classes/VueRegistry.js';

// All Vue controllers
import ListPanel from '../lib/pkp/js/controllers/list/ListPanel.vue';
import SubmissionsListPanel from '../lib/pkp/js/controllers/list/submissions/SubmissionsListPanel.vue';

// Expose Vue, the registry and controllers in a global var
window.pkp = {
	vue: Vue,
	registry: VueRegistry,
	eventBus: new Vue(),
	controllers: {
		'ListPanel': ListPanel,
		'SubmissionsListPanel': SubmissionsListPanel,
	},
	/**
	 * Helper function to determine if the current user has a role
	 *
	 * @param string|array role The key name of the role to check for
	 * @return bool
	 */
	userHasRole: function(role) {

		if (typeof role === 'string') {
			role = [role];
		}

		for (var r in role) {
			if ($.pkp.currentUser.accessRoles.indexOf($.pkp.app.accessRoles[role[r]]) > -1) {
				return true;
			}
		}
	}
};
