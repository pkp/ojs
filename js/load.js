/**
 * @file js/load.js
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Compiler entry point for building the JavaScript package. File imports
 *  using the `@` symbol are aliased to `lib/ui-library/src`.
 */
import PkpLoad from '../lib/pkp/js/load.js';

// Import controllers used by OJS
import Container from '@/components/SettingsContainer/SettingsContainer.vue';
import ListPanel from '@/components/ListPanel/ListPanel.vue';
import MultilingualProgress from '@/components/MultilingualProgress/MultilingualProgress.vue';
import SubmissionsListPanel from '@/components/ListPanel/submissions/SubmissionsListPanel.vue';
import SelectListPanel from '@/components/SelectListPanel/SelectListPanel.vue';
import SelectSubmissionsListPanel from '@/components/SelectListPanel/submissions/SelectSubmissionsListPanel.vue';
import SelectReviewerListPanel from '@/components/SelectListPanel/users/SelectReviewerListPanel.vue';
import Tooltip from '@/components/Tooltip/Tooltip.vue';

// Expose Vue, the registry and controllers in a global var
window.pkp = Object.assign(PkpLoad, {
	controllers: {
		Container,
		ListPanel,
		MultilingualProgress,
		SubmissionsListPanel,
		SelectListPanel,
		SelectSubmissionsListPanel,
		SelectReviewerListPanel,
		Tooltip,
	},
});
