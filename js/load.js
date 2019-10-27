/**
 * @file js/load.js
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Compiler entry point for building the JavaScript package. File imports
 *  using the `@` symbol are aliased to `lib/ui-library/src`.
 */
import PkpLoad from '../lib/pkp/js/load.js';

// Import controllers used by OJS
import ListPanel from '@/components/ListPanel/ListPanel.vue';
import SubmissionsListPanel from '@/components/ListPanel/submissions/SubmissionsListPanel.vue';
import SelectListPanel from '@/components/SelectListPanel/SelectListPanel.vue';
import SelectSubmissionsListPanel from '@/components/SelectListPanel/submissions/SelectSubmissionsListPanel.vue';
import SelectReviewerListPanel from '@/components/SelectListPanel/users/SelectReviewerListPanel.vue';
import Statistics from '@/components/Statistics/Statistics.vue';
import StatisticsEditorial from '@/components/Statistics/StatisticsEditorial.vue';
import StatisticsSubmissions from '@/components/Statistics/StatisticsSubmissions.vue';

// Expose Vue, the registry and controllers in a global var
window.pkp = Object.assign(PkpLoad, {
	controllers: {
		ListPanel,
		SubmissionsListPanel,
		SelectListPanel,
		SelectSubmissionsListPanel,
		SelectReviewerListPanel,
		Statistics,
		StatisticsEditorial,
		StatisticsSubmissions,
	},
});
